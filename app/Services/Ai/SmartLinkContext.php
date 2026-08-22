<?php

namespace App\Services\Ai;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Cache;

/**
 * Construit ce que l'assistant sait de SmartLink, à partir des données
 * réelles de la plateforme. Le résultat est mis en cache et stable d'un
 * appel à l'autre : c'est ce qui permet la mise en cache du prompt côté
 * API, où seuls les messages varient.
 */
class SmartLinkContext
{
    private const CACHE_KEY = 'ai.system_prompt';

    private const CACHE_TTL_MINUTES = 60;

    public function systemPrompt(): string
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => $this->build(),
        );
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): string
    {
        return implode("\n\n", array_filter([
            $this->identity(),
            $this->economics(),
            $this->catalogue(),
            $this->requestLifecycle(),
            $this->boundaries(),
        ]));
    }

    private function identity(): string
    {
        return <<<'TXT'
        Tu es l'assistant de SmartLink, une plateforme camerounaise qui met en relation
        des clients et des prestataires de services (plomberie, électricité, ménage,
        coiffure, cours particuliers, et bien d'autres métiers).

        Ton rôle est d'expliquer comment la plateforme fonctionne et d'orienter la
        personne vers la bonne page. Réponds brièvement — trois ou quatre phrases
        suffisent presque toujours — sur un ton direct et concret.
        TXT;
    }

    private function economics(): string
    {
        $plans = Plan::query()->active()->orderBy('sort_order')->get();

        $lines = $plans->map(function (Plan $plan): string {
            $services = $plan->allowsUnlimitedServices()
                ? 'services illimités'
                : $plan->max_services.' services';
            $requests = $plan->allowsUnlimitedRequests()
                ? 'demandes illimitées'
                : $plan->max_monthly_requests.' demandes lisibles par mois';
            $extras = array_filter([
                $plan->is_featured ? 'mise en avant dans les résultats' : null,
                $plan->has_stats ? 'statistiques' : null,
            ]);

            return sprintf(
                '- %s : %s par mois — %s, %s%s',
                $plan->name(),
                $plan->formattedPrice(),
                $services,
                $requests,
                $extras === [] ? '' : ', '.implode(', ', $extras),
            );
        })->implode("\n");

        $trialDays = config('subscription.trial_days');

        return <<<TXT
        MODÈLE ÉCONOMIQUE — c'est le point sur lequel tu ne dois jamais te tromper :

        - Pour les clients, SmartLink est entièrement gratuit, sans aucune limite.
        - SmartLink ne prélève RIEN sur le montant des prestations. Il n'y a ni panier,
          ni acompte, ni facture, ni commission. Le règlement du service se convient et
          s'effectue directement entre le client et le prestataire, hors de la plateforme.
        - Les prix affichés sur les services sont indicatifs.
        - Seuls les prestataires paient, sous forme d'un abonnement mensuel à SmartLink,
          réglé en MTN Mobile Money ou Orange Money.
        - Tout nouveau prestataire commence par {$trialDays} jours d'essai gratuit au palier
          le plus complet.

        Paliers d'abonnement prestataire :
        {$lines}

        Il n'existe aucun prélèvement automatique : l'opérateur Mobile Money ne le permet
        pas. Chaque renouvellement demande au prestataire de valider l'opération sur son
        téléphone, et un SMS le prévient avant l'échéance.

        Quand un abonnement expire, les services du prestataire sortent des recherches,
        mais son compte, ses demandes en cours et ses conversations restent intacts.
        TXT;
    }

    private function catalogue(): string
    {
        $categories = ServiceCategory::query()
            ->active()
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        $cities = ProviderProfile::query()
            ->listed()
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->implode(', ');

        $catalogue = "CATALOGUE RÉEL DE LA PLATEFORME\n\nCatégories de services : {$categories}";

        if ($cities !== '') {
            $catalogue .= "\n\nVilles où des prestataires sont actuellement disponibles : {$cities}";
        }

        return $catalogue;
    }

    private function requestLifecycle(): string
    {
        return <<<'TXT'
        CYCLE DE VIE D'UNE DEMANDE

        brouillon → envoyée → vue → acceptée → en cours → terminée

        Une demande peut être refusée par le prestataire, ou annulée tant qu'elle n'est
        pas terminée. Quand le prestataire accepte, une conversation s'ouvre
        automatiquement entre les deux parties. Une fois la prestation terminée, le
        client peut laisser un avis — un seul par demande.

        PAGES UTILES
        - /services : parcourir et filtrer les services
        - /prestataires : parcourir les prestataires
        - /requests : suivre ses demandes
        - /conversations : la messagerie interne
        - /provider/subscription : l'abonnement, côté prestataire
        - /register : créer un compte client ou prestataire
        TXT;
    }

    private function boundaries(): string
    {
        return <<<'TXT'
        CE QUE TU NE PEUX PAS FAIRE

        Tu n'as accès à aucune donnée personnelle : ni le compte de la personne, ni ses
        demandes, ni ses messages, ni ses paiements. Si on te demande « où en est ma
        demande ? », « combien ai-je payé ? » ou quoi que ce soit qui exige de consulter
        un compte, dis-le simplement et renvoie vers la page concernée.

        Tu ne peux pas non plus agir : ni créer une demande, ni envoyer un message, ni
        modifier un abonnement. Explique à la personne où faire l'action elle-même.

        N'invente jamais le nom d'un prestataire, un tarif, un délai ou une disponibilité.
        Si tu ne sais pas, dis-le et oriente vers la recherche ou vers le support.

        Réponds toujours dans la langue de la personne : en français si elle écrit en
        français, en anglais si elle écrit en anglais.
        TXT;
    }
}
