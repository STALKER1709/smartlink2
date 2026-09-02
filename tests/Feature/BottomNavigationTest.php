<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NavigationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La barre d'onglets basse est la navigation mobile, et la seule.
 *
 * Elle coexistait avec un menu burger qui rendait exactement les mêmes quatre
 * destinations : la même liste à deux endroits, dont l'un en haut à droite de
 * l'écran, là où le pouce n'arrive pas. Ce qui doit tenir désormais : chaque
 * rôle y trouve ses destinations, le burger ne revient pas, et ce qu'il
 * portait seul reste joignable.
 */
class BottomNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le burger est parti, et rien ne doit le ramener. Le signe qui ne trompe
     * pas : le panneau qu'il ouvrait, et les liens qu'il rendait en double.
     */
    public function test_no_burger_menu_survives(): void
    {
        $rendus = [
            $this->get(route('services.index')),
            $this->actingAs(User::factory()->provider()->create())->get(route('dashboard')),
        ];

        foreach ($rendus as $rendu) {
            $rendu->assertOk()
                ->assertDontSee('id="menu-mobile"', false)
                ->assertDontSee('aria-controls="menu-mobile"', false)
                ->assertDontSee('x-responsive-nav-link', false);
        }
    }

    /**
     * Ce que le burger portait seul devait trouver une place, sans quoi le
     * retirer aurait rendu ces réglages injoignables sur un téléphone : la
     * langue et le schéma de couleurs n'existaient nulle part ailleurs sous
     * 768 px.
     */
    public function test_the_account_sheet_carries_what_the_burger_alone_had(): void
    {
        $prestataire = User::factory()->provider()->create();

        $rendu = $this->actingAs($prestataire)->get(route('dashboard'))->assertOk();

        $rendu->assertSee('id="feuille-compte"', false)
            ->assertSee('aria-controls="feuille-compte"', false)
            // La déconnexion, la langue, et les écrans propres au rôle.
            ->assertSee(route('logout'))
            ->assertSee(route('locale.switch', 'en'))
            ->assertSee(route('provider.subscription.show'));

    }

    /**
     * Pour un visiteur, la feuille porte les deux portes d'entrée.
     *
     * Test à part, et non la suite du précédent : `actingAs` vaut pour toutes
     * les requêtes du même test, et la requête « visiteur » y partait encore
     * authentifiée. Le premier jet de ce contrôle a signalé un lien
     * d'inscription manquant qui n'a jamais manqué.
     */
    public function test_the_sheet_offers_a_guest_both_doors(): void
    {
        $this->get(route('services.index'))->assertOk()
            ->assertSee(route('register'))
            ->assertSee(route('login'))
            ->assertSee(route('locale.switch', 'fr'));
    }

    public function test_a_guest_gets_the_public_destinations(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee(route('providers.index'))
            ->assertSee(route('help.index'))
            // La connexion n'est plus un onglet : elle est dans la feuille du
            // cinquième, avec l'inscription.
            ->assertSee(route('login'));
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
