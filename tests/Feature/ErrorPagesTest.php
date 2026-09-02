<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
     * Les quatre pages que l'application rend normalement.
     *
     * Elles ne sont atteintes qu'au travers d'une exception : les rendre
     * directement est le seul moyen d'en éprouver le contenu, et il vaut
     * mieux que de les laisser sans contrôle.
     */
    #[DataProvider('pagesDuSite')]
    public function test_each_error_page_names_itself_and_offers_a_way_out(string $code, string $phrase): void
    {
        $rendu = view("errors.{$code}", ['exception' => new HttpException((int) $code)])->render();

        $this->assertStringContainsString(e($phrase), $rendu);
        // Le nom du site, et au moins une sortie.
        $this->assertStringContainsString('SmartLink', $rendu);
        $this->assertMatchesRegularExpression('/<a [^>]*href=|<button/', $rendu);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pagesDuSite(): array
    {
        return [
            '403 refus' => ['403', 'Cette page ne vous est pas ouverte'],
            '404 introuvable' => ['404', "Cette page n'existe pas"],
            '419 session expirée' => ['419', 'Votre page a expiré'],
            '429 trop de tentatives' => ['429', 'Vous allez un peu vite'],
        ];
    }

    /**
     * La limitation de débit annonce son délai dans l'en-tête `Retry-After`.
     * Le dire vaut mieux qu'un « patientez » sans durée : sans chiffre, on
     * réessaie tout de suite et le compteur repart.
     */
    public function test_the_rate_limit_page_says_how_long_to_wait(): void
    {
        $rendu = view('errors.429', [
            'exception' => new HttpException(429, '', null, ['Retry-After' => 42]),
        ])->render();

        $this->assertStringContainsString('42', $rendu);
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
        foreach (['errors/500.blade.php', 'errors/503.blade.php', 'errors/partials/autonome.blade.php'] as $fichier) {
            // Sans ses commentaires : celui d'en-tête nomme précisément les
            // directives à ne pas employer, pour dire pourquoi.
            $source = (string) preg_replace(
                '/\{\{--.*?--\}\}/s', '',
                (string) file_get_contents(resource_path('views/'.$fichier)),
            );

            foreach (['@vite', '@auth', '@guest', 'x-guest-layout', 'x-app-layout', 'x-error-panel', 'Auth::', 'route('] as $dependance) {
                $this->assertStringNotContainsString($dependance, $source,
                    "{$fichier} ne doit dépendre de rien : « {$dependance} » y est.");
            }
        }

        // Et les deux doivent tout de même dire ce qu'elles ont à dire.
        $cinqCents = view('errors.500')->render();
        $this->assertStringContainsString(e(__("Quelque chose s'est cassé de notre côté")), $cinqCents);
        $this->assertStringContainsString(__('Réessayer'), $cinqCents);
        $this->assertStringContainsString('SmartLink', $cinqCents);

        $maintenance = view('errors.503')->render();
        $this->assertStringContainsString(__('SmartLink revient dans quelques minutes'), $maintenance);
    }
}
