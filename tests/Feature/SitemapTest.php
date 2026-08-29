<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le plan du site ne doit annoncer que ce qu'un visiteur non connecté peut
 * réellement ouvrir. Une URL qui répond 404 n'est pas une erreur bénigne :
 * répétée, elle fait baisser la confiance accordée au plan tout entier, et la
 * fiche d'un prestataire cesse d'être ouverte sans que rien ne change dans son
 * URL — abonnement échu, plafond mensuel atteint, compte supprimé.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function serviceDeProfil(bool $listed): Service
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'is_listed' => $listed,
        ]);

        return Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => ServiceCategory::factory()->create()->id,
            'status' => Service::STATUS_ACTIVE,
        ]);
    }

    public function test_the_sitemap_is_well_formed_xml(): void
    {
        $this->serviceDeProfil(listed: true);

        $reponse = $this->get('/sitemap.xml');

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($reponse->getContent());

        $this->assertNotFalse($xml, 'Le plan du site doit être un XML analysable.');
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_it_lists_the_public_pages(): void
    {
        $reponse = $this->get('/sitemap.xml');

        foreach (['home', 'services.index', 'providers.index', 'help.index', 'legal.terms'] as $route) {
            $reponse->assertSee('<loc>'.route($route).'</loc>', false);
        }
    }

    public function test_it_lists_a_service_a_visitor_can_open(): void
    {
        $service = $this->serviceDeProfil(listed: true);

        $this->get(route('services.show', $service))->assertOk();
        $this->get('/sitemap.xml')->assertSee('<loc>'.route('services.show', $service).'</loc>', false);
    }

    /**
     * Le cas qui compte : la fiche existe, son URL n'a pas bougé, mais elle
     * répond 404 parce que le prestataire est sorti des recherches.
     */
    public function test_it_omits_a_service_whose_provider_is_hidden(): void
    {
        $service = $this->serviceDeProfil(listed: false);

        $this->get(route('services.show', $service))->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee(route('services.show', $service), false);
    }

    public function test_it_omits_a_service_that_is_not_active(): void
    {
        $service = $this->serviceDeProfil(listed: true);
        $service->update(['status' => Service::STATUS_INACTIVE]);

        $this->get('/sitemap.xml')->assertDontSee(route('services.show', $service), false);
    }

    /**
     * La suppression d'un compte est douce : le profil reste en base avec son
     * `is_listed` d'origine. Seule l'absence du compte le retire du plan.
     */
    public function test_it_omits_the_profile_of_a_departed_account(): void
    {
        $service = $this->serviceDeProfil(listed: true);
        $profile = $service->provider->providerProfile;

        $service->provider->delete();

        $this->get('/sitemap.xml')
            ->assertDontSee(route('providers.show', $profile), false)
            ->assertDontSee(route('services.show', $service), false);
    }

    /**
     * La page est publique et sans authentification, et son coût croît avec le
     * catalogue. Sans mémoire, n'importe qui peut la marteler et c'est la base
     * qui paie.
     */
    public function test_it_is_built_once_and_then_served_from_memory(): void
    {
        $this->serviceDeProfil(listed: true);

        $this->get('/sitemap.xml')->assertOk();

        DB::enableQueryLog();
        $this->get('/sitemap.xml')->assertOk();
        $requetes = DB::getQueryLog();
        DB::disableQueryLog();

        $balayages = array_filter(
            $requetes,
            fn (array $r) => str_contains($r['query'], 'from "services"')
                || str_contains($r['query'], 'from "provider_profiles"'),
        );

        $this->assertSame([], $balayages, 'Le second passage ne doit plus interroger le catalogue.');
    }

    /**
     * `trustHosts` accepte plusieurs noms légitimes — le domaine et, sur
     * Vercel, l'adresse de déploiement. Le plan contient des URL absolues :
     * une clé de cache unique servirait à l'un le plan de l'autre, et les
     * moteurs recevraient un plan pointant hors du domaine exploré.
     */
    public function test_each_host_gets_its_own_sitemap(): void
    {
        $premier = $this->get('http://smartlink.test/sitemap.xml')->getContent();
        $second = $this->get('http://apercu.smartlink.test/sitemap.xml')->getContent();

        $this->assertStringContainsString('http://smartlink.test/', $premier);
        $this->assertStringContainsString('http://apercu.smartlink.test/', $second);
        $this->assertStringNotContainsString('apercu.smartlink.test', $premier);
    }

    public function test_the_robots_file_points_at_the_sitemap(): void
    {
        $reponse = $this->get('/robots.txt');

        $reponse->assertOk();
        $reponse->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        // L'adresse doit être absolue : une adresse relative n'est pas lue.
        $reponse->assertSee('Sitemap: '.route('sitemap'), false);
        $this->assertStringContainsString('://', route('sitemap'));
    }

    public function test_the_robots_file_still_closes_the_private_areas(): void
    {
        $reponse = $this->get('/robots.txt');

        $reponse->assertSee('Disallow: /admin', false);
        $reponse->assertSee('Disallow: /dashboard', false);
        $reponse->assertDontSee('Disallow: /services', false);
    }
}
