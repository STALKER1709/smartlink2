<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * La route de passage quotidien remplace `schedule:run` sur les hébergements
 * serverless. Elle déclenche une commande qui touche à tous les abonnements :
 * elle doit refuser tout appel qui n'apporte pas le secret.
 */
class CronRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_when_no_secret_is_configured(): void
    {
        config(['cron.secret' => null]);

        $response = $this->getJson(route('cron.subscriptions.refresh'));

        $response->assertStatus(503);
    }

    public function test_it_refuses_a_call_without_a_token(): void
    {
        config(['cron.secret' => 'secret-de-test']);

        $response = $this->getJson(route('cron.subscriptions.refresh'));

        $response->assertForbidden();
    }

    public function test_it_refuses_a_wrong_token(): void
    {
        config(['cron.secret' => 'secret-de-test']);

        $response = $this->getJson(route('cron.subscriptions.refresh'), [
            'Authorization' => 'Bearer mauvais-secret',
        ]);

        $response->assertForbidden();
    }

    public function test_it_runs_the_refresh_with_the_right_token(): void
    {
        config(['cron.secret' => 'secret-de-test']);

        $response = $this->getJson(route('cron.subscriptions.refresh'), [
            'Authorization' => 'Bearer secret-de-test',
        ]);

        $response->assertOk();
        $response->assertJson([
            'command' => 'subscriptions:refresh',
            'status' => 'ok',
        ]);
    }

    public function test_the_refresh_command_stays_reachable(): void
    {
        $this->assertArrayHasKey('subscriptions:refresh', Artisan::all());
    }
}
