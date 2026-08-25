<?php

namespace Tests\Feature\Subscription;

use App\Contracts\PaymentProvider;
use App\Contracts\WebhookEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\CollectionResult;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Comportement du contrôleur de rappel, indépendamment du fournisseur :
 * l'authentification et la lecture lui sont déléguées, le contrôleur décide
 * seulement de ce qu'il en fait.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le rappel vient de l'extérieur et ne peut pas porter de jeton CSRF. La
     * protection étant désactivée d'office pendant les tests, l'exemption se
     * vérifie sur la configuration du middleware lui-même.
     */
    public function test_the_webhook_path_is_exempt_from_csrf_verification(): void
    {
        $middleware = $this->app->make(PreventRequestForgery::class);

        $this->assertContains('payments/webhook', $middleware->getExcludedPaths());
    }

    public function test_a_confirmed_payment_extends_the_subscription(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;
        $this->fakeProvider($payment->internal_reference, 'success');

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame('HRSK-REF', $payment->provider_reference);
        $this->assertNotNull($payment->paid_at);

        $subscription = $payment->subscription->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->greaterThan($endsAt));
    }

    public function test_a_refused_payment_is_recorded_without_extending_anything(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;
        $this->fakeProvider($payment->internal_reference, 'failed');

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertEquals(
            $endsAt->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    /**
     * PENDING et HOLD — la revue anti-blanchiment — ne concluent rien. Créditer
     * sur un HOLD reviendrait à ouvrir un abonnement encore refusable.
     */
    public function test_a_status_that_settles_nothing_leaves_the_payment_pending(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, null);

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'pending']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    /**
     * Le statut annoncé dans le rappel n'est jamais cru : le contrôleur relit
     * l'état chez le fournisseur, qui seul fait foi.
     */
    public function test_the_announced_status_is_never_trusted_over_the_api(): void
    {
        $payment = $this->pendingPayment();

        // Le rappel prétend « SUCCESS », l'API dit que rien n'est réglé.
        $this->fakeProvider($payment->internal_reference, 'failed');

        $this->postJson(route('payments.webhook'), ['status' => 'SUCCESS'])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    public function test_an_unauthenticated_callback_is_refused(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'success', authentic: false);

        $this->postJson(route('payments.webhook'), [])
            ->assertForbidden()
            ->assertJson(['status' => 'rejected']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    /**
     * HR-Skills envoie depuis sa console des événements de test qui ne portent
     * aucun paiement. Ils sont authentiques : les refuser ferait croire au
     * fournisseur que notre point d'entrée est en panne, et l'a effectivement
     * fait en production. On en accuse réception.
     */
    public function test_an_authentic_callback_without_a_payment_is_acknowledged(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider(null, 'success');

        $this->postJson(route('payments.webhook'), ['event' => 'webhook.test'])
            ->assertOk()
            ->assertJson(['status' => 'acknowledged']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_replaying_a_callback_does_not_extend_the_subscription_twice(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'success');

        $this->postJson(route('payments.webhook'), [])->assertOk();
        $afterFirst = $payment->subscription->fresh()->ends_at;

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertEquals(
            $afterFirst->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    public function test_a_callback_on_an_unknown_payment_is_acknowledged_without_effect(): void
    {
        $this->fakeProvider('SL-INCONNUE', 'success');

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    /**
     * Le fournisseur est remplacé par un double : le contrôleur ne doit rien
     * savoir de la signature ni de la forme des charges utiles.
     */
    private function fakeProvider(?string $internalReference, ?string $status, bool $authentic = true): void
    {
        $fake = new class($internalReference, $status, $authentic) implements PaymentProvider
        {
            public function __construct(
                private readonly ?string $internalReference,
                private readonly ?string $status,
                private readonly bool $authentic,
            ) {}

            public function collect(
                string $phone,
                string $operator,
                int $amountXaf,
                string $description,
                string $reference,
            ): CollectionResult {
                return CollectionResult::pending('HRSK-REF');
            }

            public function status(string $providerReference): ?string
            {
                return $this->status;
            }

            public function isAuthentic(Request $request): bool
            {
                return $this->authentic;
            }

            public function readWebhook(Request $request): ?WebhookEvent
            {
                return $this->internalReference === null
                    ? null
                    : new WebhookEvent('HRSK-REF', $this->internalReference);
            }
        };

        $this->app->instance(PaymentProvider::class, $fake);
    }

    private function pendingPayment(): Payment
    {
        $provider = User::factory()->provider()->create();
        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(3),
        ]);

        return Payment::factory()->create([
            'subscription_id' => $subscription->id,
            'payer_id' => $provider->id,
            'amount_xaf' => $plan->price_xaf,
        ]);
    }
}
