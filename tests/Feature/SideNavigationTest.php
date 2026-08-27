<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\NavigationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le tiroir latéral de bureau.
 *
 * Deux choses s'y cassent en silence. Les deux navigations peuvent se
 * retrouver à l'écran en même temps — rien ne plante, l'écran porte
 * simplement deux fois les mêmes liens à deux endroits. Et la table du menu
 * du compte, partagée avec la barre horizontale, peut redevenir deux listes
 * recopiées : l'écran ajouté d'un seul côté reste atteignable, il disparaît
 * juste de l'autre chemin.
 */
class SideNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create();
    }

    public function test_the_drawer_and_the_top_bar_never_show_together(): void
    {
        $html = $this->actingAs(User::factory()->client()->create())
            ->get(route('dashboard'))
            ->getContent();

        // Le tiroir paraît à partir de `xl`, la barre horizontale s'efface au
        // même seuil. Si l'un des deux seuils bouge sans l'autre, l'écran
        // porte deux navigations ou plus aucune.
        $this->assertStringContainsString('xl:flex', $html);
        $this->assertStringContainsString('xl:hidden', $html);
    }

    public function test_the_drawer_carries_the_paths_of_each_role(): void
    {
        $cas = [
            [User::factory()->client()->create(), 'dashboard', ['favorites.index', 'disputes.index', 'client.profile.edit']],
            [User::factory()->provider()->create(), 'dashboard', ['provider.reviews.index', 'provider.transactions.index', 'provider.subscription.show']],
            [User::factory()->admin()->create(), 'admin.dashboard', ['admin.disputes.index', 'requests.index']],
        ];

        foreach ($cas as [$utilisateur, $accueil, $routes]) {
            $reponse = $this->actingAs($utilisateur)->get(route($accueil));

            $reponse->assertOk();

            foreach ($routes as $route) {
                $reponse->assertSee(route($route), false);
            }

            // Et le pied du tiroir : la langue et la sortie.
            $reponse->assertSee(route('locale.switch', 'fr'), false);
            $reponse->assertSee(route('logout'), false);
        }
    }

    public function test_the_login_screen_has_no_drawer(): void
    {
        // L'écran de connexion emploie un autre gabarit : une navigation
        // complète y proposerait des chemins qu'on ne peut pas suivre.
        $this->get(route('login'))->assertDontSee('xl:flex', false);
    }

    public function test_the_statistics_entry_follows_the_plan(): void
    {
        $prestataire = User::factory()->provider()->create();

        $this->assertFalse(
            NavigationLinks::secondaires($prestataire)->contains('route', 'provider.statistics.index'),
            'Sans forfait à statistiques, l\'entrée mène à un écran refusé.'
        );

        $plan = Plan::factory()->create(['has_stats' => true, 'code' => 'pro-stats']);
        Subscription::factory()->create([
            'user_id' => $prestataire->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue(
            NavigationLinks::secondaires($prestataire->fresh())->contains('route', 'provider.statistics.index')
        );
    }

    public function test_every_ligature_of_the_tables_is_in_the_icon_subset(): void
    {
        // `icones()` travaille sur un utilisateur fabriqué de toutes pièces,
        // sans clé en base : la table complète doit s'obtenir sans requête.
        $ligatures = NavigationLinks::icones();

        foreach (['dashboard', 'badge', 'favorite', 'flag', 'insights', 'card_membership', 'receipt_long', 'settings', 'help'] as $attendue) {
            $this->assertContains($attendue, $ligatures);
        }

        $this->assertSame([], array_diff($ligatures, config('icons.names', [])));
    }
}
