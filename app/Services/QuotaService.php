<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ProviderProfile;
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
     * Décompte une demande nouvellement lue, puis réévalue la visibilité :
     * un prestataire au plafond sort des recherches, pour qu'aucun client
     * ne lui écrive sans pouvoir être lu.
     */
    public function consumeRequestRead(User $provider): void
    {
        $profile = $provider->providerProfile;

        if ($profile === null) {
            return;
        }

        $period = $this->currentPeriod();

        $profile->forceFill([
            'requests_read_count' => $profile->requests_read_period === $period
                ? $profile->requests_read_count + 1
                : 1,
            'requests_read_period' => $period,
        ])->save();

        $this->refreshListing($provider);
    }

    /**
     * Recalcule la visibilité d'un prestataire dans les recherches.
     */
    public function refreshListing(User $provider): bool
    {
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
