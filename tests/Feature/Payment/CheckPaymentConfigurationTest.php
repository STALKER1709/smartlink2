<?php

namespace Tests\Feature\Payment;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `payment:check` est le seul moyen de savoir si l'encaissement fonctionnera
 * avant qu'un prestataire ne l'apprenne à ses dépens. Il doit donc échouer
 * bruyamment sur chacune des configurations qui cassent en silence.
 */
class CheckPaymentConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payment.driver', 'hrskills');
        config()->set('payment.hrskills.key_a', 'hrsk_pk_test_aaaaaaaaaaaa');
        config()->set('payment.hrskills.key_b', 'hrsk_sk_test_bbbbbbbbbbbb');
        config()->set('payment.hrskills.webhook_secret', 'secret-de-rappel');
        config()->set('payment.hrskills.base_url', 'https://api.hrskills-pay.com');
    }

    public function test_the_mock_driver_never_fails_the_check(): void
    {
        config()->set('payment.driver', 'mock');

        $this->artisan('payment:check')->assertSuccessful();
    }

    public function test_a_coherent_configuration_passes_without_touching_the_network(): void
    {
        Http::fake();

        $this->artisan('payment:check')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_keys_from_different_environments_are_refused(): void
    {
        config()->set('payment.hrskills.key_b', 'hrsk_sk_live_bbbbbbbbbbbb');

        $this->artisan('payment:check')->assertFailed();
    }

    public function test_a_missing_webhook_secret_is_refused(): void
    {
        config()->set('payment.hrskills.webhook_secret', '');

        $this->artisan('payment:check')->assertFailed();
    }

    /**
     * L'erreur qu'aucun œil ne rattrape : coller une clé dans l'interface de
     * l'hébergeur y capture le retour à la ligne, et HR-Skills répond
     * « clé inconnue » sans que rien ne paraisse anormal côté configuration.
     */
    public function test_a_key_with_surrounding_whitespace_is_refused(): void
    {
        config()->set('payment.hrskills.key_a', "hrsk_pk_test_aaaaaaaaaaaa\n");

        $this->artisan('payment:check')->assertFailed();
    }

    public function test_an_empty_base_url_is_refused(): void
    {
        config()->set('payment.hrskills.base_url', '');

        $this->artisan('payment:check')->assertFailed();
    }

    public function test_the_live_probe_passes_when_the_keys_are_accepted(): void
    {
        Http::fake([
            '*/v1/auth/transaction-token' => Http::response(['transaction_token' => 'jeton', 'expires_in' => 2700]),
        ]);

        $this->artisan('payment:check', ['--live' => true])->assertSuccessful();
    }

    public function test_the_live_probe_fails_when_the_keys_are_refused(): void
    {
        Http::fake([
            '*/v1/auth/transaction-token' => Http::response(
                ['code' => 'invalid_api_key', 'message' => 'record not found'], 401,
            ),
        ]);

        $this->artisan('payment:check', ['--live' => true])->assertFailed();
    }

    /**
     * Si le bac à sable ne sert l'échange de token que sous « /sandbox », le
     * diagnostic doit le dire au lieu de s'en contenter : c'est le fournisseur
     * qu'il faudrait alors corriger.
     */
    public function test_a_token_obtained_only_under_sandbox_is_reported_as_a_defect(): void
    {
        Http::fake([
            'api.hrskills-pay.com/sandbox/v1/auth/transaction-token' => Http::response(
                ['transaction_token' => 'jeton', 'expires_in' => 2700],
            ),
            'api.hrskills-pay.com/v1/auth/transaction-token' => Http::response(['message' => 'not found'], 404),
        ]);

        $this->artisan('payment:check', ['--live' => true])->assertFailed();
    }

    /**
     * Une clé de production ne doit jamais faire tâter « /sandbox » : ce serait
     * envoyer le secret de production sur un chemin qui n'est pas le sien.
     */
    public function test_a_live_key_is_never_probed_under_sandbox(): void
    {
        config()->set('payment.hrskills.key_a', 'hrsk_pk_live_aaaaaaaaaaaa');
        config()->set('payment.hrskills.key_b', 'hrsk_sk_live_bbbbbbbbbbbb');

        Http::fake([
            '*/v1/auth/transaction-token' => Http::response(['message' => 'refusé'], 401),
        ]);

        $this->artisan('payment:check', ['--live' => true])->assertFailed();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/sandbox'));
    }
}
