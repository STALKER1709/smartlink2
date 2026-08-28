<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;

/**
 * Ce qu'un prestataire a le droit de faire en ce moment, d'après le palier
 * de son abonnement en cours. Sans abonnement valide, tout est fermé sauf
 * ce qu'il a déjà engagé : ses demandes en cours et ses conversations.
 */
class QuotaService
{
    public function plan(User $provider): ?Plan
    {
        return $provider->currentPlan();
    }

    public function servicesUsed(User $provider): int
    {
        return $provider->services()->count();
    }

    public function canPublishService(User $provider): bool
    {
        $plan = $this->plan($provider);

        if ($plan === null) {
            return false;
        }

        return $plan->allowsUnlimitedServices()
            || $this->servicesUsed($provider) < $plan->max_services;
    }

    /**
     * Demandes déjà lues ce mois-ci. Le compteur se remet à zéro de lui-même
     * au changement de mois : aucune tâche planifiée n'est nécessaire pour
     * que le décompte reparte juste.
     */
    public function requestsRead(User $provider): int
    {
        $profile = $provider->providerProfile;

        if ($profile === null || $profile->requests_read_period !== $this->currentPeriod()) {
            return 0;
        }

        return $profile->requests_read_count;
    }

    public function hasRequestQuotaLeft(User $provider): bool
    {
        $plan = $this->plan($provider);

        if ($plan === null) {
            return false;
        }

        return $plan->allowsUnlimitedRequests()
            || $this->requestsRead($provider) < $plan->max_monthly_requests;
    }

    public function remainingRequests(User $provider): ?int
    {
        $plan = $this->plan($provider);

        if ($plan === null) {
            return 0;
        }

        if ($plan->allowsUnlimitedRequests()) {
            return null;
        }

        return max(0, $plan->max_monthly_requests - $this->requestsRead($provider));
    }

    /**
     * Réserve une lecture de demande, puis réévalue la visibilité : un
     * prestataire au plafond sort des recherches, pour qu'aucun client ne lui
     * écrive sans pouvoir être lu.
     *
     * Renvoie `false` quand le plafond est déjà atteint — l'appelant ne doit
     * alors rien montrer de la demande.
     *
     * Vérifier le plafond puis incrémenter en deux temps laissait passer deux
     * lectures simultanées : les deux processus lisaient le même compteur et
     * écrivaient la même valeur, ce qui perdait une incrémentation et
     * distribuait deux fois le dernier jeton. Tout tient donc en une seule
     * écriture conditionnelle, et c'est la base qui arbitre : la clause WHERE
     * porte le plafond, le nombre de lignes touchées dit si la place était
     * libre. `UPDATE` prend un verrou sur la ligne, les deux appels ne peuvent
     * pas réussir.
     */
    public function consumeRequestRead(User $provider): bool
    {
        $profile = $provider->providerProfile;
        $plan = $this->plan($provider);

        if ($profile === null || $plan === null) {
            return false;
        }

        $period = $this->currentPeriod();

        // Bascule de mois, en une écriture conditionnelle : seul le processus
        // qui trouve encore la période révolue remet le compteur à zéro. Les
        // autres n'y touchent pas — sans cette condition, une remise à zéro
        // tardive effacerait l'incrémentation d'un processus plus rapide.
        ProviderProfile::query()
            ->whereKey($profile->id)
            ->where(function ($query) use ($period) {
                $query->where('requests_read_period', '!=', $period)
                    ->orWhereNull('requests_read_period');
            })
            ->update([
                'requests_read_count' => 0,
                'requests_read_period' => $period,
            ]);

        // La période est désormais celle du mois courant : le plafond porte
        // donc directement sur la colonne. C'est la base qui arbitre, et
        // `increment` compile en « SET count = count + 1 », qui ne relit pas
        // une valeur chargée en mémoire.
        $reserve = ProviderProfile::query()
            ->whereKey($profile->id)
            ->where('requests_read_period', $period)
            ->when(
                ! $plan->allowsUnlimitedRequests(),
                fn ($query) => $query->where('requests_read_count', '<', $plan->max_monthly_requests)
            )
            ->increment('requests_read_count');

        if ($reserve === 0) {
            return false;
        }

        // L'instance en mémoire porte encore l'ancien compteur : la visibilité
        // se recalculerait sur une valeur périmée.
        $provider->unsetRelation('providerProfile');

        $this->refreshListing($provider);

        return true;
    }

    /**
     * Ramène le nombre de services actifs sous le plafond du palier.
     *
     * Sans ce passage, le plafond ne s'appliquerait qu'à la publication : un
     * prestataire qui publie dix services pendant son essai au palier Pro les
     * garderait tous visibles en retombant sur un palier qui n'en autorise
     * qu'un. Le plafond ne vaudrait alors que pour les nouveaux venus.
     *
     * Les plus anciens survivent, arbitrairement mais de façon stable : rien
     * ne permet de deviner lequel compte le plus, et un ordre qui change à
     * chaque passage ferait clignoter les annonces. Les surnuméraires passent
     * en « inactif » — ils restent modifiables et reparaissent tels quels si
     * le prestataire reprend un palier plus large.
     *
     * @return int nombre de services retirés
     */
    public function enforceServiceCap(User $provider): int
    {
        $plan = $this->plan($provider);

        if ($plan === null || $plan->allowsUnlimitedServices()) {
            return 0;
        }

        $surplus = $provider->services()
            ->where('status', Service::STATUS_ACTIVE)
            ->orderBy('id')
            ->pluck('id')
            ->slice($plan->max_services);

        if ($surplus->isEmpty()) {
            return 0;
        }

        Service::whereIn('id', $surplus)->update(['status' => Service::STATUS_INACTIVE]);

        return $surplus->count();
    }

    /**
     * Recalcule la visibilité d'un prestataire dans les recherches.
     */
    public function refreshListing(User $provider): bool
    {
        // Le plafond s'applique d'abord, et sans dépendre du profil : un
        // prestataire sans fiche publique n'apparaît dans aucune recherche,
        // mais ses services, eux, existent bel et bien.
        $this->enforceServiceCap($provider);

        $profile = $provider->providerProfile;

        if ($profile === null) {
            return false;
        }

        $plan = $this->plan($provider);
        $listed = $plan !== null && $this->hasRequestQuotaLeft($provider);
        $promoted = $listed && $plan->is_featured;

        if ($profile->is_listed !== $listed || $profile->is_promoted !== $promoted) {
            $profile->forceFill([
                'is_listed' => $listed,
                'is_promoted' => $promoted,
            ])->save();
        }

        return $listed;
    }

    /**
     * @return array{listed: int, unlisted: int}
     */
    public function refreshAllListings(): array
    {
        $counts = ['listed' => 0, 'unlisted' => 0];

        ProviderProfile::with('user')->chunkById(200, function ($profiles) use (&$counts) {
            foreach ($profiles as $profile) {
                if ($profile->user === null) {
                    continue;
                }

                $counts[$this->refreshListing($profile->user) ? 'listed' : 'unlisted']++;
            }
        });

        return $counts;
    }

    private function currentPeriod(): string
    {
        return now()->format('Y-m');
    }
}
