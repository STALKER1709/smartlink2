<?php

namespace Tests\Feature\Payment;

use App\Services\Payment\CollectionResult;
use App\Services\Payment\HrSkillsPayProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HrSkillsPayProviderTest extends TestCase
{
    use RefreshDatabase;

    private HrSkillsPayProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payment.driver', 'hrskills');
        config()->set('payment.hrskills.key_a', 'hrsk_pk_test_a');
        config()->set('payment.hrskills.key_b', 'hrsk_sk_test_b');
        config()->set('payment.hrskills.webhook_secret', 'secret-de-rappel');
        config()->set('payment.hrskills.base_url', 'https://api.hrskills-pay.com');

        Cache::flush();
        $this->provider = $this->app->make(HrSkillsPayProvider::class);
    }

    public function test_a_collection_goes_out_pending_with_a_provider_reference(): void
    {
        $this->fakeApi();

        $result = $this->provider->collect('677123456', 'MTN', 2500, 'Abonnement', 'SL-ABC123DEF456');

        $this->assertSame(CollectionResult::STATUS_PENDING, $result->status);
        $this->assertSame('ref_abc', $result->providerReference);
    }

    public function test_the_request_carries_both_keys_and_a_stable_idempotency_key(): void
    {
        $this->fakeApi();

        $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-ABC123DEF456');
        $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-ABC123DEF456');

        $keys = [];

        Http::assertSent(function ($request) use (&$keys) {
            if (! str_contains($request->url(), '/payin/')) {
                return true;
            }

            $this->assertSame('Bearer hrsk_pk_test_a', $request->header('Authorization')[0]);
            $this->assertSame('jeton-de-transaction', $request->header('X-Transaction-Token')[0]);
            $this->assertSame('hrsk_sk_test_b', $request->header('X-API-Secret')[0]);
            $keys[] = $request->header('Idempotency-Key')[0];

            return true;
        });

        // Deux tentatives sur le même paiement, une seule clé : c'est ce qui
        // empêche l'API de créer un second encaissement.
        $this->assertCount(2, $keys);
        $this->assertSame($keys[0], $keys[1]);
    }

    public function test_the_payload_follows_the_documented_shape(): void
    {
        $this->fakeApi();

        $this->provider->collect('+237 677 12 34 56', 'ORANGE', 7500, 'Abonnement Pro', 'SL-XYZ');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/payin/')) {
                return true;
            }

            $this->assertSame('orange', $request['operator']);
            $this->assertSame('CM', $request['country']);
            $this->assertSame('237677123456', $request['phone_number']);
            $this->assertSame(7500, $request['amount']);
            $this->assertSame('XAF', $request['currency']);
            $this->assertSame('SL-XYZ', $request['metadata']['reference_interne']);

            return true;
        });
    }

    /**
     * Avec des clés de test, l'encaissement n'est servi que sous « /sandbox » ;
     * l'échange de clés contre un token, lui, passe par le chemin normal.
     */
    public function test_test_keys_route_the_collection_through_the_sandbox(): void
    {
        $this->fakeApi();

        $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-ABC');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.hrskills-pay.com/sandbox/api/v1/payin/mobile-money'
            || str_contains($request->url(), '/v1/auth/transaction-token'));
    }

    public function test_the_transaction_token_is_reused_rather_than_refetched(): void
    {
        $this->fakeApi();

        $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-A');
        $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-B');

        $tokenCalls = 0;

        Http::assertSent(function ($request) use (&$tokenCalls) {
            if (str_contains($request->url(), 'transaction-token')) {
                $tokenCalls++;
            }

            return true;
        });

        $this->assertSame(1, $tokenCalls, 'le token doit être mis en cache entre deux appels');
    }

    public function test_an_unknown_operator_is_refused_before_any_call(): void
    {
        Http::preventStrayRequests();

        $result = $this->provider->collect('677123456', 'camtel', 2500, 'Abonnement', 'SL-ABC');

        $this->assertSame(CollectionResult::STATUS_FAILED, $result->status);
    }

    public function test_an_unusable_phone_number_is_refused_before_any_call(): void
    {
        Http::preventStrayRequests();

        $result = $this->provider->collect('12345', 'mtn', 2500, 'Abonnement', 'SL-ABC');

        $this->assertSame(CollectionResult::STATUS_FAILED, $result->status);
    }

    public function test_a_refused_collection_is_reported_as_a_failure(): void
    {
        Http::fake([
            '*/transaction-token' => Http::response(['transaction_token' => 'jeton', 'expires_in' => 2700]),
            '*/payin/*' => Http::response(['error' => 'insufficient_funds'], 402),
        ]);

        $result = $this->provider->collect('677123456', 'mtn', 2500, 'Abonnement', 'SL-ABC');

        $this->assertSame(CollectionResult::STATUS_FAILED, $result->status);
    }

    public function test_the_status_is_read_from_the_api_and_mapped(): void
    {
        Http::fake([
            '*/transaction-token' => Http::response(['transaction_token' => 'jeton', 'expires_in' => 2700]),
            '*/v1/payments/*' => Http::response(['data' => ['status' => 'SUCCESS']]),
        ]);

        $this->assertSame('success', $this->provider->status('ref_abc'));
    }

    public function test_a_hold_status_settles_nothing(): void
    {
        Http::fake([
            '*/transaction-token' => Http::response(['transaction_token' => 'jeton', 'expires_in' => 2700]),
            '*/v1/payments/*' => Http::response(['data' => ['status' => 'HOLD']]),
        ]);

        $this->assertNull($this->provider->status('ref_abc'));
    }

    public function test_a_correctly_signed_callback_is_read(): void
    {
        $body = json_encode([
            'data' => ['reference' => 'ref_abc', 'metadata' => ['reference_interne' => 'SL-ABC']],
        ]);

        $event = $this->provider->readWebhook($this->signedRequest($body, 'secret-de-rappel'));

        $this->assertNotNull($event);
        $this->assertSame('ref_abc', $event->providerReference);
        $this->assertSame('SL-ABC', $event->internalReference);
    }

    public function test_a_callback_signed_with_the_wrong_secret_is_refused(): void
    {
        $body = json_encode(['data' => ['reference' => 'ref_abc']]);

        $this->assertNull($this->provider->readWebhook($this->signedRequest($body, 'mauvais-secret')));
    }

    public function test_without_a_configured_secret_every_callback_is_refused(): void
    {
        config()->set('payment.hrskills.webhook_secret', '');
        $body = json_encode(['data' => ['reference' => 'ref_abc']]);

        // Sans secret, n'importe qui pourrait déclarer un abonnement réglé.
        $this->assertNull($this->provider->readWebhook($this->signedRequest($body, 'peu-importe')));
    }

    public function test_an_authentic_but_unreadable_callback_is_refused(): void
    {
        $this->assertNull($this->provider->readWebhook(
            $this->signedRequest('ceci n\'est pas du json', 'secret-de-rappel'),
        ));
    }

    private function signedRequest(string $body, string $signingSecret): Request
    {
        $request = Request::create('/payments/webhook', 'POST', [], [], [], [], $body);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Hub-Signature', 'sha256='.hash_hmac('sha256', $body, $signingSecret));

        return $request;
    }

    private function fakeApi(): void
    {
        Http::fake([
            '*/transaction-token' => Http::response([
                'transaction_token' => 'jeton-de-transaction',
                'expires_in' => 2700,
            ]),
            '*/payin/*' => Http::response(['data' => ['reference' => 'ref_abc', 'status' => 'PENDING']], 202),
        ]);
    }
}
