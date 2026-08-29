<?php

namespace Tests\Feature;

use App\Services\DeploymentCheckService;
use Illuminate\Http\Middleware\TrustHosts;
use Tests\TestCase;

/**
 * Les noms d'hôte auxquels l'application accepte de répondre.
 *
 * Laravel compose ses URL absolues à partir de l'en-tête « Host » de la
 * requête — et de « X-Forwarded-Host », honoré parce que tous les répartiteurs
 * sont de confiance (bootstrap/app.php). C'est-à-dire à partir d'une valeur
 * que le client choisit.
 *
 * Sans filtrage, une demande de mot de passe oublié envoyée avec un Host
 * falsifié fait partir, vers la boîte du titulaire, un lien de
 * réinitialisation valide qui pointe chez l'attaquant. Il suffit que la
 * victime clique pour que le jeton lui soit livré : c'est une prise de compte
 * complète, sans qu'aucune protection de mot de passe n'ait à céder.
 *
 * Le middleware ne s'applique ni en local ni sous tests — c'est Laravel qui en
 * décide, et c'est voulu. Ces tests portent donc sur les motifs eux-mêmes,
 * qui sont la partie que ce dépôt écrit.
 */
class TrustedHostsTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    private function motifs(): array
    {
        return (new TrustHosts($this->app))->hosts();
    }

    private function accepte(string $host): bool
    {
        foreach ($this->motifs() as $motif) {
            if (preg_match('{'.$motif.'}i', $host)) {
                return true;
            }
        }

        return false;
    }

    public function test_the_configured_domain_and_its_subdomains_are_accepted(): void
    {
        config(['app.url' => 'https://smartlink.cm']);

        $this->assertTrue($this->accepte('smartlink.cm'));
        $this->assertTrue($this->accepte('www.smartlink.cm'));
    }

    public function test_any_other_domain_is_rejected(): void
    {
        config(['app.url' => 'https://smartlink.cm']);

        $this->assertFalse($this->accepte('attaquant.example'));

        // Le piège du préfixe : un motif écrit sans ancrage laisserait passer
        // un domaine qui contient le nom légitime sans être lui.
        $this->assertFalse($this->accepte('smartlink.cm.attaquant.example'));
        $this->assertFalse($this->accepte('pas-smartlink.cm'));
    }

    /**
     * Les déploiements de prévisualisation portent un nom tiré au sort à
     * chaque poussée : les nommer un par un est impossible.
     */
    public function test_vercel_preview_deployments_are_accepted_on_that_host(): void
    {
        config(['app.url' => 'https://smartlink.cm']);
        putenv('VERCEL=1');

        try {
            $this->assertTrue($this->accepte('apercu-xyz123.vercel.app'));
            $this->assertFalse($this->accepte('attaquant.example'));
        } finally {
            putenv('VERCEL');
        }
    }

    public function test_vercel_previews_are_not_accepted_on_a_classic_host(): void
    {
        config(['app.url' => 'https://smartlink.cm']);

        $this->assertFalse($this->accepte('apercu-xyz123.vercel.app'));
    }

    /**
     * Un APP_URL sans hôte vide la liste, et une liste vide ne restreint rien.
     * C'est le bon compromis — mieux vaut le filtrage éteint qu'un site qui
     * répond 400 à tout le monde — mais c'est aussi pourquoi `deploy:check`
     * refuse un APP_URL mal formé en production.
     */
    public function test_an_empty_app_url_degrades_open_rather_than_closing_the_site(): void
    {
        config(['app.url' => '']);

        $this->assertSame([], array_filter($this->motifs()));

        // Le garde-fou correspondant : en production, deploy:check refuse un
        // APP_URL qui laisserait le filtrage éteint.
        $this->app->detectEnvironment(fn () => 'production');

        $appUrl = array_values(array_filter(
            app(DeploymentCheckService::class)->run(),
            fn (array $c) => $c['name'] === 'APP_URL',
        ));

        $this->assertNotEmpty($appUrl, 'deploy:check doit contrôler APP_URL en production.');
        $this->assertSame(DeploymentCheckService::ERROR, $appUrl[0]['status']);
    }
}
