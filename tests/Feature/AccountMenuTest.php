<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\NavigationLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le menu du compte — ce qui ne se consulte pas tous les jours.
 *
 * Sa table vit dans `NavigationLinks::secondaires()` et non dans la vue :
 * recopiée, elle dérive au premier écran ajouté d'un seul côté, et le défaut
 * est muet — l'écran reste atteignable, il disparaît juste d'un chemin.
 */
class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create();
    }

    public function test_the_menu_carries_the_paths_of_each_role(): void
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

            // Et la sortie, au pied du menu.
            $reponse->assertSee(route('logout'), false);
        }
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

        $this->assertSame([], array_diff($ligatures, array_keys(config('icons.correspondance', []))));
    }
}
