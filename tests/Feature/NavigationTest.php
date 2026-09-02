<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La barre de navigation dépend du rôle : chacun y voit sa boucle quotidienne,
 * le reste passe dans le menu du compte. Une entrée oubliée pour un seul rôle
 * ne se voit pas — la page s'affiche, le chemin disparaît simplement.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create();
    }

    public function test_a_guest_sees_the_public_paths_and_the_two_entry_points(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('services.index'));
        $response->assertSee(route('providers.index'));
        $response->assertSee(route('help.index'));
        $response->assertSee(route('login'));
        $response->assertSee(route('register'));
    }

    public function test_a_client_reaches_search_requests_and_messages_in_one_click(): void
    {
        $response = $this->actingAs(User::factory()->client()->create())->get(route('dashboard'));

        $response->assertOk();
        foreach (['services.index', 'providers.index', 'requests.index', 'conversations.index'] as $route) {
            $response->assertSee(route($route));
        }

        // Reléguées au menu du compte, mais toujours atteignables.
        $response->assertSee(route('client.profile.edit'));
        $response->assertSee(route('profile.edit'));
        $response->assertSee(route('help.index'));
    }

    public function test_a_provider_reaches_their_daily_loop_and_keeps_their_money_pages(): void
    {
        $response = $this->actingAs(User::factory()->provider()->create())->get(route('dashboard'));

        $response->assertOk();
        foreach (['dashboard', 'requests.index', 'conversations.index', 'provider.services.index'] as $route) {
            $response->assertSee(route($route));
        }

        foreach (['provider.profile.edit', 'provider.subscription.show', 'provider.reviews.index', 'provider.transactions.index'] as $route) {
            $response->assertSee(route($route), false);
        }
    }

    public function test_an_admin_reaches_moderation_first(): void
    {
        // /dashboard redirige l'administrateur vers son propre écran.
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee(route('requests.index'));
    }

    public function test_the_bar_stays_short_enough_to_fit(): void
    {
        foreach ([
            User::factory()->client()->create(),
            User::factory()->provider()->create(),
            User::factory()->admin()->create(),
        ] as $utilisateur) {
            $url = $utilisateur->isAdmin() ? route('admin.dashboard') : route('dashboard');
            $html = $this->actingAs($utilisateur)->get($url)->getContent();

            // La barre large : on compte les liens de premier niveau.
            preg_match('/sm:ms-8 sm:flex sm:flex-nowrap">(.*?)<\/div>/s', $html, $m);
            $niveau1 = substr_count($m[1] ?? '', '<a ');

            $this->assertLessThanOrEqual(4, $niveau1,
                "Plus de quatre entrées de premier niveau pour un {$utilisateur->role} : la barre déborde.");
        }
    }
}
