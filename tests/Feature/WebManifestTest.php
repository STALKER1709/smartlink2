<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'installation sur l'écran d'accueil échoue en silence : un manifeste
 * incomplet n'affiche aucune erreur, le navigateur cesse simplement de
 * proposer l'installation. Ces tests tiennent les conditions que Chrome exige
 * — un nom, une icône de 192 et une de 512, un `start_url` dans la portée, un
 * mode d'affichage autonome.
 */
class WebManifestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function manifeste(): array
    {
        $reponse = $this->get('/manifest.json');
        $reponse->assertOk();

        return $reponse->json();
    }

    /**
     * Un manifeste servi en `application/octet-stream` est ignoré sans
     * message : c'est le genre de panne qu'on ne trouve qu'en cherchant.
     */
    public function test_it_is_served_with_the_manifest_media_type(): void
    {
        $this->get('/manifest.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
    }

    public function test_it_carries_what_an_installation_requires(): void
    {
        $manifeste = $this->manifeste();

        $this->assertNotEmpty($manifeste['name']);
        $this->assertSame('standalone', $manifeste['display']);
        // La barre finale compte : la portée est comparée comme un préfixe
        // d'URL, et sans elle un domaine voisin au nom plus long y tomberait.
        $this->assertSame(rtrim(url('/'), '/').'/', $manifeste['start_url']);
        $this->assertSame(rtrim(url('/'), '/').'/', $manifeste['scope']);

        // Un `short_name` trop long est tronqué sous l'icône.
        $this->assertLessThanOrEqual(12, mb_strlen($manifeste['short_name']));
    }

    public function test_it_offers_both_sizes_and_a_maskable_icon(): void
    {
        $icones = $this->manifeste()['icons'];

        $tailles = array_column($icones, 'sizes');
        $this->assertContains('192x192', $tailles);
        $this->assertContains('512x512', $tailles);

        // Sans entrée « maskable », Android encadre l'icône de blanc.
        $this->assertContains('maskable', array_column($icones, 'purpose'));
    }

    /**
     * Une icône déclarée mais absente coûte l'installation, exactement comme
     * une icône non déclarée.
     */
    public function test_every_declared_icon_exists_at_its_declared_size(): void
    {
        foreach ($this->manifeste()['icons'] as $icone) {
            $chemin = public_path(parse_url($icone['src'], PHP_URL_PATH));

            $this->assertFileExists($chemin, "Icône déclarée mais absente : {$icone['src']}");

            [$largeur, $hauteur] = getimagesize($chemin);
            $this->assertSame(
                $icone['sizes'],
                "{$largeur}x{$hauteur}",
                "L'icône {$icone['src']} ne fait pas la taille annoncée.",
            );
        }
    }

    /**
     * Les raccourcis de l'appui long. Un raccourci vers un espace privé
     * n'ouvrirait qu'un écran de connexion.
     */
    public function test_the_shortcuts_point_at_pages_a_visitor_can_open(): void
    {
        foreach ($this->manifeste()['shortcuts'] as $raccourci) {
            $this->get($raccourci['url'])->assertOk();
        }
    }

    public function test_the_offline_page_needs_nothing_from_the_network(): void
    {
        $page = $this->get(route('offline'))->assertOk()->getContent();

        // Ni police distante, ni feuille de style, ni script compilé : la page
        // s'affiche au moment précis où rien de tout cela n'est joignable.
        $this->assertStringNotContainsString('fonts.googleapis.com', $page);
        $this->assertStringNotContainsString('<link rel="stylesheet"', $page);
        $this->assertStringNotContainsString('/build/', $page);

        $this->assertStringContainsString('noindex', $page);
    }

    /**
     * Le service worker ne met en cache que la page de repli. S'il servait des
     * pages du site depuis un cache, il afficherait des données périmées — une
     * demande déjà acceptée, un abonnement déjà réglé — sans recours.
     */
    public function test_the_service_worker_only_caches_the_offline_page(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("'/hors-ligne'", $sw);
        $this->assertStringContainsString("requete.mode !== 'navigate'", $sw);
        $this->assertStringNotContainsString('cache.put', $sw);
    }

    public function test_the_layouts_declare_the_manifest(): void
    {
        $this->get(route('home'))
            ->assertSee('rel="manifest"', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('rel="apple-touch-icon"', false);

        $this->get(route('login'))->assertSee('rel="manifest"', false);
    }
}
