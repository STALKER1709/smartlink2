<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\DeploymentCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le contrôle d'après-déploiement n'a d'intérêt que s'il voit les pannes
 * silencieuses. Chaque test ci-dessous reproduit une configuration qui laisse
 * l'application démarrer normalement tout en cassant une de ses fonctions.
 */
class DeploymentCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function test_an_empty_plans_table_is_an_error(): void
    {
        Plan::query()->forceDelete();

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Formules'));
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
            'queue.default' => 'sync',
            'cron.secret' => 'un-secret',
            'logging.default' => 'stderr',
        ]);

        $checks = app(DeploymentCheckService::class)->run();

        $this->assertFalse(app(DeploymentCheckService::class)->hasErrors($checks));
    }

    public function test_the_health_route_refuses_a_call_without_the_secret(): void
    {
        config(['cron.secret' => 'un-secret']);

        $this->getJson(route('cron.health'))->assertForbidden();
    }

    public function test_the_health_route_reports_the_checks_with_the_secret(): void
    {
        config(['cron.secret' => 'un-secret']);

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
