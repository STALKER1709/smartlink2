<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptions = $this->app->make(SubscriptionService::class);
    }

    public function test_a_new_provider_gets_a_trial_carrying_the_top_plan(): void
    {
        Plan::factory()->pro()->create();
        $provider = User::factory()->provider()->create();

        $subscription = $this->subscriptions->startTrial($provider);

        $this->assertNotNull($subscription);
        $this->assertSame(Subscription::STATUS_TRIALING, $subscription->status);
        $this->assertSame(Plan::CODE_PRO, $subscription->plan->code);
        $this->assertSame(config('subscription.trial_days'), $subscription->daysRemaining());
        $this->assertTrue($subscription->isUsable());
    }

    public function test_a_client_never_gets_a_trial(): void
    {
        Plan::factory()->pro()->create();
        $client = User::factory()->client()->create();

        $this->assertNull($this->subscriptions->startTrial($client));
        $this->assertSame(0, Subscription::count());
    }

    public function test_the_trial_is_granted_only_once(): void
    {
        Plan::factory()->pro()->create();
        $provider = User::factory()->provider()->create();

        $this->subscriptions->startTrial($provider);
        $this->assertNull($this->subscriptions->startTrial($provider));
        $this->assertSame(1, Subscription::count());
    }

    public function test_a_payment_on_a_live_subscription_adds_a_cycle_to_the_due_date(): void
    {
        $subscription = Subscription::factory()->create(['ends_at' => now()->addDays(4)]);
        $expected = $subscription->ends_at->copy()->addDays(config('subscription.cycle_days'));

        $payment = Payment::factory()->successful()->create([
            'subscription_id' => $subscription->id,
            'payer_id' => $subscription->user_id,
        ]);

        $renewed = $this->subscriptions->recordSuccessfulPayment($payment);

        $this->assertSame(Subscription::STATUS_ACTIVE, $renewed->status);
        $this->assertEqualsWithDelta($expected->timestamp, $renewed->ends_at->timestamp, 2);
    }

    public function test_a_payment_on_a_lapsed_subscription_restarts_the_cycle_from_now(): void
    {
        $subscription = Subscription::factory()->expired()->create();
        $expected = now()->addDays(config('subscription.cycle_days'));

        $payment = Payment::factory()->successful()->create([
            'subscription_id' => $subscription->id,
            'payer_id' => $subscription->user_id,
        ]);

        $renewed = $this->subscriptions->recordSuccessfulPayment($payment);

        $this->assertSame(Subscription::STATUS_ACTIVE, $renewed->status);
        $this->assertEqualsWithDelta($expected->timestamp, $renewed->ends_at->timestamp, 2);
        $this->assertTrue($renewed->isUsable());
    }
}
