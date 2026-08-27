<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Les destinations principales de chaque rôle.
 *
 * Cette table était construite dans `layouts/navigation.blade.php`. La barre
 * d'onglets du bas, incluse séparément, n'y a pas accès : les variables d'un
 * `@include` ne traversent pas vers un autre. La recopier aurait ramené le
 * défaut que cette liste unique corrigeait — une entrée ajoutée d'un côté et
 * manquante de l'autre.
 *
 * Le contenu dépend du rôle parce que la boucle quotidienne diffère : le
 * client cherche puis demande, le prestataire répond puis gère ses services,
 * l'administrateur modère.
 *
 * `court` et `icone` ne servent qu'à la barre du bas : à cinq onglets sur
 * 390 px, chacun dispose de 78 px et « Tableau de bord » n'y entre pas.
 *
 * @phpstan-type Lien array{route: string, motif: string, libelle: string, court: string, icone: string}
 */
class NavigationLinks
{
    /**
     * @return Collection<int, array<string, string>>
     */
    public static function principaux(?User $utilisateur): Collection
    {
        if ($utilisateur === null) {
            return collect([
                self::lien('services.index', 'services.*', __('Services'), __('Services'), 'search'),
                self::lien('providers.index', 'providers.*', __('Prestataires'), __('Prestataires'), 'person_search'),
                self::lien('help.index', 'help.*', 'Aide', 'Aide', 'chat'),
            ]);
        }

        if ($utilisateur->isClient()) {
            return collect([
                self::lien('services.index', 'services.*', __('Services'), __('Services'), 'search'),
                self::lien('providers.index', 'providers.*', __('Prestataires'), __('Prestataires'), 'person_search'),
                self::lien('requests.index', 'requests.*', __('Demandes'), __('Demandes'), 'inbox'),
                self::lien('conversations.index', 'conversations.*', __('Messages'), __('Messages'), 'forum'),
            ]);
        }

        if ($utilisateur->isProvider()) {
            return collect([
                self::lien('dashboard', 'dashboard', __('Tableau de bord'), __('Accueil'), 'home'),
                self::lien('requests.index', 'requests.*', __('Demandes'), __('Demandes'), 'inbox'),
                self::lien('conversations.index', 'conversations.*', __('Messages'), __('Messages'), 'forum'),
                self::lien('provider.services.index', 'provider.services.*', __('Mes services'), __('Services'), 'home_repair_service'),
            ]);
        }

        return collect([
            self::lien('admin.dashboard', 'admin.*', __('Administration'), __('Admin'), 'shield'),
            self::lien('services.index', 'services.*', __('Services'), __('Services'), 'search'),
            self::lien('providers.index', 'providers.*', __('Prestataires'), __('Prestataires'), 'person_search'),
            self::lien('conversations.index', 'conversations.*', __('Messages'), __('Messages'), 'forum'),
        ]);
    }

    /**
     * Ce qui ne se consulte pas tous les jours : le profil du rôle, ses écrans
     * propres, l'aide et les réglages.
     *
     * Cette table était construite dans `layouts/navigation.blade.php`. Elle
     * en est sortie pour la raison qui avait déjà sorti `principaux()` : le
     * menu déroulant et le panneau mobile sont deux rendus distincts, et une
     * liste recopiée dérive au premier écran ajouté d'un seul côté.
     *
     * `$toutes` demande la table complète, entrées conditionnelles comprises :
     * `icones()` doit énumérer toutes les ligatures possibles, et elle
     * travaille sur un utilisateur fabriqué de toutes pièces, sans abonnement
     * ni clé en base à interroger.
     *
     * @return Collection<int, array<string, string>>
     */
    public static function secondaires(?User $utilisateur, bool $toutes = false): Collection
    {
        if ($utilisateur === null) {
            return collect();
        }

        $entrees = collect();

        if ($utilisateur->isClient()) {
            $entrees->push(self::entree('dashboard', __('Tableau de bord'), 'dashboard'));
            $entrees->push(self::entree('client.profile.edit', __('Mon profil client'), 'badge'));
            $entrees->push(self::entree('favorites.index', 'Mes favoris', 'favorite'));
            $entrees->push(self::entree('disputes.index', 'Mes signalements', 'flag'));
        } elseif ($utilisateur->isProvider()) {
            $entrees->push(self::entree('provider.profile.edit', __('Mon profil prestataire'), 'badge'));

            if ($toutes || $utilisateur->currentPlan()?->has_stats) {
                $entrees->push(self::entree('provider.statistics.index', __('ui.nav.statistics'), 'insights'));
            }

            $entrees->push(self::entree('provider.subscription.show', __('ui.nav.subscription'), 'card_membership'));
            $entrees->push(self::entree('provider.reviews.index', 'Mes avis', 'star'));
            $entrees->push(self::entree('provider.transactions.index', 'Mes transactions', 'receipt_long'));
            $entrees->push(self::entree('disputes.index', 'Mes signalements', 'flag'));
        } else {
            $entrees->push(self::entree('requests.index', __('Demandes'), 'inbox'));
            $entrees->push(self::entree('admin.disputes.index', 'Signalements', 'flag'));
        }

        $entrees->push(self::entree('help.index', 'Aide', 'help'));
        $entrees->push(self::entree('profile.edit', __('Paramètres du compte'), 'settings'));

        return $entrees;
    }

    /**
     * L'entrée de compte de la barre du bas : la porte du profil, ou de la
     * connexion pour un visiteur.
     *
     * @return array<string, string>
     */
    public static function compte(?User $utilisateur): array
    {
        if ($utilisateur === null) {
            return ['route' => 'login', 'motif' => 'login', 'court' => __('Connexion'), 'icone' => 'lock'];
        }

        $route = match (true) {
            $utilisateur->isProvider() => 'provider.profile.edit',
            $utilisateur->isClient() => 'client.profile.edit',
            default => 'profile.edit',
        };

        return ['route' => $route, 'motif' => '*profile*', 'court' => __('Profil'), 'icone' => 'person'];
    }

    /**
     * Toutes les ligatures que cette table peut produire.
     *
     * Elles sont rendues par une expression et n'apparaissent donc jamais
     * littéralement dans le balisage : `icons:sync` ne peut pas les y trouver.
     * Plutôt que de les recopier dans une constante — qui aurait dérivé au
     * premier onglet ajouté — la liste se déduit des tables elles-mêmes.
     *
     * @return array<int, string>
     */
    public static function icones(): array
    {
        $roles = [null];

        foreach ([User::ROLE_CLIENT, User::ROLE_PROVIDER, User::ROLE_ADMIN] as $role) {
            $utilisateur = new User;
            $utilisateur->role = $role;
            $roles[] = $utilisateur;
        }

        return collect($roles)
            ->flatMap(fn (?User $u) => self::principaux($u)
                ->pluck('icone')
                ->concat(self::secondaires($u, toutes: true)->pluck('icone'))
                ->push(self::compte($u)['icone']))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function lien(string $route, string $motif, string $libelle, string $court, string $icone): array
    {
        return compact('route', 'motif', 'libelle', 'court', 'icone');
    }

    /**
     * @return array<string, string>
     */
    private static function entree(string $route, string $libelle, string $icone): array
    {
        return compact('route', 'libelle', 'icone');
    }
}
