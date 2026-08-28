<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\DeploymentCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le contrôle d'après-déploiement n'a d'intérêt que s'il voit les pannes
 * silencieuses. Chaque test ci-dessous reproduit une configuration qui laisse
 * l'application démarrer normalement tout en cassant une de ses fonctions.
 */
class DeploymentCheckTest extends TestCase
{
    use RefreshDatabase;

    private string $defaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultConnection = (string) config('database.default');

        // Une base sans formule est un vrai point bloquant — c'est justement
        // ce que le contrôle signale. Ces tests portent sur autre chose : on
        // part donc d'une installation complète.
        Plan::factory()->create();
    }

    private function statusOf(string $name): ?string
    {
        foreach (app(DeploymentCheckService::class)->run() as $check) {
            if ($check['name'] === $name) {
                return $check['status'];
            }
        }

        return null;
    }

    private function pretendServerless(): void
    {
        putenv('VERCEL=1');
    }

    protected function tearDown(): void
    {
        putenv('VERCEL');

        // RefreshDatabase ouvre sa transaction sur la connexion par défaut au
        // démarrage et la referme au démontage. Un test qui bascule la
        // connexion par défaut la laisserait ouverte sur l'ancienne, et le
        // test suivant échouerait sur « cannot start a transaction within a
        // transaction » — une panne sans rapport avec ce qu'il vérifie.
        config(['database.default' => $this->defaultConnection]);

        parent::tearDown();
    }

    public function test_an_empty_plans_table_is_an_error(): void
    {
        Plan::query()->forceDelete();

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Formules'));
    }

    public function test_a_zero_day_cycle_is_an_error(): void
    {
        // Ce qu'on obtient quand la variable existe sur l'hébergeur mais vide :
        // (int) '' vaut zéro, et l'abonnement payé ne couvre rien.
        config(['subscription.cycle_days' => 0]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('SUBSCRIPTION_CYCLE_DAYS'));
    }

    public function test_a_relative_payment_url_is_an_error(): void
    {
        config([
            'payment.driver' => 'hrskills',
            'payment.hrskills.webhook_secret' => 'un-secret',
            'payment.hrskills.base_url' => '',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Paiement'));
    }

    public function test_an_absolute_payment_url_passes(): void
    {
        config([
            'payment.driver' => 'hrskills',
            'payment.hrskills.webhook_secret' => 'un-secret',
            'payment.hrskills.base_url' => 'https://api.hrskills-pay.com',
        ]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Paiement'));
    }

    public function test_a_migration_pushed_but_never_applied_is_an_error(): void
    {
        // On efface la trace de la dernière migration : elle redevient « en
        // attente » sans que le schéma bouge, exactement comme après un
        // déploiement où personne n'a lancé migrate.
        $derniere = DB::table('migrations')->orderByDesc('id')->first();
        DB::table('migrations')->where('id', $derniere->id)->delete();

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Migrations en attente'));
    }

    public function test_an_up_to_date_schema_passes(): void
    {
        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Migrations en attente'));
    }

    public function test_an_unencrypted_postgres_connection_is_an_error(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'inexistant.invalid',
            'database.connections.pgsql.sslmode' => 'prefer',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('DB_SSLMODE'));
    }

    public function test_a_required_ssl_mode_passes(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'inexistant.invalid',
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('DB_SSLMODE'));
    }

    public function test_a_direct_postgres_host_is_flagged_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'database.connections.pgsql.host' => 'db.abcdef.supabase.invalid',
        ]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Mutualisation'));
    }

    public function test_a_pooled_postgres_host_is_not_flagged(): void
    {
        $this->pretendServerless();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'database.connections.pgsql.host' => 'aws-0-eu-west-3.pooler.supabase.invalid',
        ]);

        $this->assertNull($this->statusOf('Mutualisation'));
    }

    public function test_the_transport_checks_stay_silent_on_other_engines(): void
    {
        config(['database.default' => 'sqlite']);

        $this->assertNull($this->statusOf('DB_SSLMODE'));
        $this->assertNull($this->statusOf('Mutualisation'));
    }

    public function test_a_local_media_disk_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['filesystems.media' => 'public']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_local_media_disk_is_fine_on_a_classic_host(): void
    {
        config(['filesystems.media' => 'public']);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_queued_connection_is_an_error_without_a_worker(): void
    {
        $this->pretendServerless();
        config(['queue.default' => 'database']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('QUEUE_CONNECTION'));
    }

    public function test_a_sync_queue_passes_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['queue.default' => 'sync']);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('QUEUE_CONNECTION'));
    }

    public function test_a_missing_cron_secret_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['cron.secret' => null]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('CRON_SECRET'));
    }

    public function test_an_s3_disk_without_a_public_url_is_flagged(): void
    {
        $this->pretendServerless();
        config([
            'filesystems.media' => 's3',
            'filesystems.disks.s3.url' => null,
        ]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_fully_configured_serverless_host_passes(): void
    {
        $this->pretendServerless();
        config([
            'filesystems.media' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.example.com',
            // Les pièces d'identité ont leur propre seau, fermé : un disque
            // local les perdrait au déploiement suivant, et la vérification
            // des prestataires s'arrêterait sans erreur visible.
            'filesystems.id_documents' => 's3_id_documents',
            'queue.default' => 'sync',
            'cron.secret' => 'un-secret',
            'logging.default' => 'stderr',
            // Sans effet quand la suite tourne sur SQLite ; indispensable
            // quand elle tourne sur PostgreSQL, où le transport est contrôlé.
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $checks = app(DeploymentCheckService::class)->run();

        $this->assertFalse(app(DeploymentCheckService::class)->hasErrors($checks));
    }

    /**
     * Le disque des pièces d'identité ne doit jamais être celui des images :
     * ce dernier est public, donc servi sans passer par Laravel.
     */
    public function test_id_documents_sharing_the_public_media_disk_is_an_error(): void
    {
        config([
            'filesystems.media' => 'public',
            'filesystems.id_documents' => 'public',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('ID_DOCUMENTS_DISK'));
    }

    public function test_a_local_id_documents_disk_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['filesystems.id_documents' => 'id_documents']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('ID_DOCUMENTS_DISK'));
    }

    public function test_the_health_route_refuses_a_call_without_the_secret(): void
    {
        config(['cron.secret' => 'un-secret']);

        $this->getJson(route('cron.health'))->assertForbidden();
    }

    public function test_the_health_route_reports_the_checks_with_the_secret(): void
    {
        config([
            'cron.secret' => 'un-secret',
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $response = $this->getJson(route('cron.health'), [
            'Authorization' => 'Bearer un-secret',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'serverless',
            'status',
            'checks' => [['name', 'status', 'message']],
        ]);
    }

    public function test_the_health_route_answers_500_when_something_blocks(): void
    {
        $this->pretendServerless();
        config([
            'cron.secret' => 'un-secret',
            'filesystems.media' => 'public',
        ]);

        $this->getJson(route('cron.health'), ['Authorization' => 'Bearer un-secret'])
            ->assertStatus(500)
            ->assertJson(['status' => 'failed', 'serverless' => true]);
    }
}
