<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le rappel de l'opérateur vient de l'extérieur et ne peut pas porter de
     * jeton CSRF. La protection étant désactivée d'office pendant les tests,
     * l'exemption se vérifie sur la configuration du middleware lui-même.
     */
    public function test_the_webhook_path_is_exempt_from_csrf_verification(): void
    {
        $middleware = $this->app->make(PreventRequestForgery::class);

        $this->assertContains('payments/webhook', $middleware->getExcludedPaths());
    }

    public function test_a_successful_callback_marks_the_payment_and_extends_the_subscription(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;

        $response = $this->postJson(route('payments.webhook'), [
            'external_reference' => $payment->internal_reference,
            'status' => 'SUCCESSFUL',
            'reference' => 'CAMPAY-123',
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame('CAMPAY-123', $payment->campay_reference);
        $this->assertNotNull($payment->paid_at);

        $subscription = $payment->subscription->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->greaterThan($endsAt));
        $this->assertTrue($subscription->isUsable());
    }

    public function test_a_failed_callback_records_the_reason_without_extending_the_subscription(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;

        $this->postJson(route('payments.webhook'), [
            'external_reference' => $payment->internal_reference,
            'status' => 'FAILED',
            'message' => 'Solde insuffisant',
        ])->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);
        $this->assertSame('Solde insuffisant', $payment->failure_reason);
        $this->assertEquals(
            $endsAt->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    public function test_replaying_a_callback_does_not_extend_the_subscription_twice(): void
    {
        $payment = $this->pendingPayment();

        $body = [
            'external_reference' => $payment->internal_reference,
            'status' => 'SUCCESSFUL',
            'reference' => 'CAMPAY-123',
        ];

        $this->postJson(route('payments.webhook'), $body)->assertOk();
        $afterFirst = $payment->subscription->fresh()->ends_at;

        $this->postJson(route('payments.webhook'), $body)->assertOk();

        $this->assertEquals(
            $afterFirst->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    public function test_a_callback_with_a_wrong_signature_is_refused(): void
    {
        config()->set('campay.webhook_secret', 'le-vrai-secret');
        $payment = $this->pendingPayment();

        $this->postJson(route('payments.webhook').'?token=mauvais', [
            'external_reference' => $payment->internal_reference,
            'status' => 'SUCCESSFUL',
        ])->assertForbidden();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_a_callback_with_the_right_signature_is_accepted(): void
    {
        config()->set('campay.webhook_secret', 'le-vrai-secret');
        $payment = $this->pendingPayment();

        $this->withHeader('X-Campay-Signature', 'le-vrai-secret')
            ->postJson(route('payments.webhook'), [
                'external_reference' => $payment->internal_reference,
                'status' => 'SUCCESSFUL',
            ])->assertOk();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
    }

    public function test_an_incomplete_callback_is_rejected(): void
    {
        $this->postJson(route('payments.webhook'), ['status' => 'SUCCESSFUL'])
            ->assertStatus(400);
    }

    public function test_an_unknown_reference_is_ignored_without_error(): void
    {
        $this->postJson(route('payments.webhook'), [
            'external_reference' => 'SL-INCONNUE',
            'status' => 'SUCCESSFUL',
        ])->assertOk();
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
