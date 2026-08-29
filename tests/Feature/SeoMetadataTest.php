<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une place de marché de proximité vit du référencement local et du partage.
 * Tant que toutes les pages portaient le même titre et aucun aperçu, un lien
 * collé dans WhatsApp n'annonçait ni métier, ni ville, ni photo — et un moteur
 * ne distinguait pas deux fiches de services.
 *
 * Ces tests montent la garde sur ce que voient les robots et les aperçus.
 */
class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    private function serviceVisible(): Service
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'business_name' => 'Plomberie Eto\'o',
        ]);

        return Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => ServiceCategory::factory()->create()->id,
            'title' => 'Réparation de fuite',
            'city' => 'Douala',
            'description' => 'Intervention rapide sur toute fuite de canalisation, jour et nuit.',
            'price_amount' => 5000,
            'price_unit' => 'intervention',
            'status' => Service::STATUS_ACTIVE,
        ]);
    }

    public function test_the_home_page_carries_a_description_and_a_share_preview(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:image"', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    }

    /**
     * Le cœur du correctif : deux fiches ne peuvent plus porter le même titre.
     */
    public function test_a_service_page_carries_its_own_title_and_city(): void
    {
        $service = $this->serviceVisible();

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertSee('<title>Réparation de fuite à Douala · '.config('app.name').'</title>', false);
    }

    public function test_a_service_share_preview_names_the_provider_and_the_price(): void
    {
        $service = $this->serviceVisible();

        $response = $this->get(route('services.show', $service));

        $response->assertSee('Plomberie Eto&#039;o', false);
        $response->assertSee('5 000 FCFA', false);
    }

    public function test_a_provider_page_carries_its_trade_and_city(): void
    {
        $categorie = ServiceCategory::factory()->create(['name' => 'Plomberie']);
        $provider = User::factory()->provider()->create();
        $profil = ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'business_name' => 'Atelier Mballa',
            'category_id' => $categorie->id,
            'city' => 'Yaoundé',
        ]);

        $response = $this->get(route('providers.show', $profil));

        $response->assertOk();
        $response->assertSee('Atelier Mballa — Plomberie à Yaoundé', false);
    }

    /**
     * Sans canonique, chaque combinaison de filtres compterait comme une page
     * distincte au contenu presque identique.
     */
    public function test_a_filtered_listing_points_back_to_its_canonical_url(): void
    {
        $response = $this->get(route('services.index', ['city' => 'Douala', 'page' => 2]));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('services.index').'">', false);
    }

    public function test_public_pages_stay_indexable(): void
    {
        $this->get(route('home'))->assertDontSee('name="robots"', false);
        $this->get(route('services.index'))->assertDontSee('name="robots"', false);
        $this->get(route('help.index'))->assertDontSee('name="robots"', false);
    }

    /**
     * Les écrans privés redirigent vers la connexion : les laisser indexables
     * gaspille le budget d'exploration et expose la structure des URL.
     */
    public function test_private_screens_are_excluded_from_the_index(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->get(route('dashboard'))
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->actingAs($client)->get(route('requests.index'))
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    /**
     * L'inscription est une page d'acquisition : elle, on la veut trouvable.
     */
    public function test_the_registration_page_stays_indexable_unlike_the_rest_of_auth(): void
    {
        $this->get(route('register'))->assertDontSee('name="robots"', false);
        $this->get(route('login'))->assertSee('name="robots"', false);
    }

    /**
     * Le fichier n'est plus déposé dans `public/` : il est servi par
     * l'application, pour que la ligne « Sitemap » porte une adresse absolue
     * juste sur chaque environnement. Le reste de son contenu ne change pas.
     * Les règles elles-mêmes sont éprouvées dans `SitemapTest`.
     */
    public function test_robots_txt_closes_the_private_areas(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringNotContainsString('Disallow: /services', $robots);
    }

    public function test_the_default_share_image_exists(): void
    {
        $this->assertFileExists(public_path('images/partage-smartlink.png'));
    }
}
