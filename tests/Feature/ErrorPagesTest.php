<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les pages d'erreur sont des pages du site.
 *
 * Elles étaient celles de Laravel : « 404 | Not Found » en anglais sur un site
 * français, sans le nom du site, sans un lien pour repartir. On y arrive par
 * un lien mort ou par un incident — deux moments où le visiteur a le plus
 * besoin qu'on lui parle.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_address_gets_the_site_own_page(): void
    {
        $this->get('/une-adresse-qui-nexiste-pas')
            ->assertNotFound()
            ->assertSee(__("Cette page n'existe pas"))
            ->assertSee(route('services.index'), false);
    }

    /**
     * Le catalogue s'offre à tout le monde ; le tableau de bord n'a de sens
     * que pour qui a un compte.
     */
    public function test_the_dashboard_is_only_offered_to_those_who_have_one(): void
    {
        $this->get('/une-adresse-qui-nexiste-pas')
            ->assertDontSee(route('dashboard'), false);

        $this->actingAs(User::factory()->client()->create())
            ->get('/une-adresse-qui-nexiste-pas')
            ->assertSee(route('dashboard'), false);
    }

    /**
     * La garde qui compte sur la page des 500.
     *
     * Elle sert au moment où l'on ne sait pas ce qui est cassé : la base peut
     * être injoignable, le manifeste de Vite absent. Tout ce qu'elle irait
     * chercher ailleurs fait partie de ce qui peut manquer à ce moment-là — et
     * une page d'erreur qui échoue à s'afficher laisse le visiteur devant
     * l'écran blanc du serveur.
     *
     * Le contrôle est donc textuel, et c'est voulu : on ne peut pas éprouver
     * en test toutes les façons dont une application casse, mais on peut
     * vérifier qu'elle ne dépend de rien.
     */
    public function test_the_server_error_page_depends_on_nothing(): void
    {
        $source = (string) file_get_contents(resource_path('views/errors/500.blade.php'));

        // Sans ses commentaires : celui d'en-tête nomme précisément les
        // directives à ne pas employer, pour dire pourquoi.
        $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);

        foreach (['@vite', '@auth', '@guest', 'x-guest-layout', 'x-app-layout', 'Auth::', 'route('] as $dependance) {
            $this->assertStringNotContainsString($dependance, $source,
                "La page des 500 ne doit dépendre de rien : « {$dependance} » y est.");
        }

        // Et elle doit tout de même dire ce qu'elle a à dire.
        $rendu = view('errors.500')->render();
        $this->assertStringContainsString(e(__("Quelque chose s'est cassé de notre côté")), $rendu);
        $this->assertStringContainsString(__('Réessayer'), $rendu);
    }
}
