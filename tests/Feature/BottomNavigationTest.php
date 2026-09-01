<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NavigationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La barre d'onglets basse est la navigation principale sur mobile. Ce qui doit
 * tenir : chaque rôle y trouve ses destinations, et aucune icône n'y manque —
 * une ligature absente du sous-ensemble s'affiche en toutes lettres.
 */
class BottomNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_gets_the_public_destinations(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee(route('login'))
            ->assertSee(route('providers.index'));
    }

    public function test_a_provider_gets_their_own_destinations(): void
    {
        $provider = User::factory()->provider()->create();

        $this->actingAs($provider)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('provider.services.index'))
            ->assertSee(route('provider.profile.edit'));
    }

    public function test_a_client_gets_their_own_destinations(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('requests.index'))
            ->assertSee(route('client.profile.edit'));
    }

    /**
     * Les onglets sont rendus par une expression : leurs noms d'icône
     * n'apparaissent nulle part dans le balisage, et `icons:sync` ne peut pas
     * les y trouver. Sans cette garde, un onglet ajouté n'aurait tout
     * simplement pas de pictogramme — un vide, sans la moindre erreur.
     */
    public function test_every_tab_icon_is_in_the_subset(): void
    {
        $sousEnsemble = array_keys(config('icons.correspondance'));

        foreach (NavigationLinks::icones() as $icone) {
            $this->assertContains(
                $icone,
                $sousEnsemble,
                "L'icône « {$icone} » de la barre d'onglets manque à la table de config/icons.php — lancer php artisan icons:sync.",
            );
        }
    }

    /**
     * Cinq onglets au maximum : au-delà, les libellés ne tiennent plus sur
     * 390 px, la largeur d'écran la plus courante.
     */
    public function test_no_role_gets_more_than_four_main_tabs(): void
    {
        foreach ([null, User::ROLE_CLIENT, User::ROLE_PROVIDER, User::ROLE_ADMIN] as $role) {
            $utilisateur = null;

            if ($role !== null) {
                $utilisateur = new User;
                $utilisateur->role = $role;
            }

            $this->assertLessThanOrEqual(4, NavigationLinks::principaux($utilisateur)->count());
        }
    }

    /**
     * La contrainte est en pixels, pas en caractères. Mesuré dans un navigateur
     * à 390 px : « Prestataires » occupe 51 px dans un onglet de 78 à 98 px
     * selon le rôle. Le seuil garde la porte contre un libellé nettement plus
     * long, sans interdire un mot juste.
     */
    public function test_the_short_labels_stay_short_enough_to_fit(): void
    {
        foreach ([null, User::ROLE_CLIENT, User::ROLE_PROVIDER, User::ROLE_ADMIN] as $role) {
            $utilisateur = null;

            if ($role !== null) {
                $utilisateur = new User;
                $utilisateur->role = $role;
            }

            foreach (NavigationLinks::principaux($utilisateur) as $lien) {
                $this->assertLessThanOrEqual(
                    13,
                    mb_strlen($lien['court']),
                    "« {$lien['court']} » est trop long pour un onglet de 78 px.",
                );
            }
        }
    }
}
