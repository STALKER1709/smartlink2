<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExpiryRemindersTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $subscriptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptions = $this->app->make(SubscriptionService::class);
        Plan::factory()->create();
        Plan::factory()->pro()->create();
    }

    public function test_a_subscription_far_from_its_due_date_is_not_reminded(): void
    {
        $subscription = $this->subscriptionEndingIn(20);

        $this->assertSame(0, $this->subscriptions->sendExpiryReminders());
        $this->assertNull($subscription->fresh()->last_reminder_day);
    }

    public function test_a_reminder_goes_out_at_the_first_threshold(): void
    {
        $subscription = $this->subscriptionEndingIn(3);

        $this->assertSame(1, $this->subscriptions->sendExpiryReminders());
        $this->assertSame(3, $subscription->fresh()->last_reminder_day);
    }

    public function test_the_same_threshold_is_never_reminded_twice(): void
    {
        $this->subscriptionEndingIn(3);

        $this->assertSame(1, $this->subscriptions->sendExpiryReminders());
        $this->assertSame(0, $this->subscriptions->sendExpiryReminders());
    }

    public function test_a_closer_threshold_triggers_a_second_reminder(): void
    {
        $subscription = $this->subscriptionEndingIn(3);
        $this->subscriptions->sendExpiryReminders();

        $subscription->forceFill(['ends_at' => now()->addHours(20)])->save();

        $this->assertSame(1, $this->subscriptions->sendExpiryReminders());
        $this->assertSame(1, $subscription->fresh()->last_reminder_day);
    }

    public function test_a_trial_gets_the_trial_wording(): void
    {
        $logged = [];
        Log::shouldReceive('channel->info')->andReturnUsing(function ($message) use (&$logged) {
            $logged[] = $message;
        });

        $this->subscriptionEndingIn(3, Subscription::STATUS_TRIALING);
        $this->subscriptions->sendExpiryReminders();

        $this->assertNotEmpty($logged);
        $this->assertStringContainsString('essai gratuit', $logged[0]);
    }

    public function test_a_subscription_whose_account_is_gone_is_skipped(): void
    {
        $subscription = $this->subscriptionEndingIn(3);
        $subscription->user->delete();

        $this->assertSame(0, $this->subscriptions->sendExpiryReminders());
        $this->assertNull($subscription->fresh()->last_reminder_day);
    }

    public function test_expiring_clears_the_reminder_state_so_the_next_cycle_starts_clean(): void
    {
        $subscription = $this->subscriptionEndingIn(3);
        $this->subscriptions->sendExpiryReminders();

        $subscription->forceFill(['ends_at' => now()->subMinute()])->save();
        $this->assertSame(1, $this->subscriptions->expireLapsed());

        $fresh = $subscription->fresh();
        $this->assertSame(Subscription::STATUS_EXPIRED, $fresh->status);
        $this->assertNull($fresh->last_reminder_day);
    }

    public function test_a_renewal_clears_the_reminder_state(): void
    {
        $subscription = $this->subscriptionEndingIn(3);
        $this->subscriptions->sendExpiryReminders();

        // Encaissement simulé : montant pair, donc succès immédiat.
        config()->set('payment.driver', 'mock');
        Http::preventStrayRequests();

        $this->subscriptions->requestPayment(
            $subscription->user,
            Plan::firstWhere('code', Plan::CODE_PRO),
            '677001122',
            'mtn',
        );

        $this->assertNull($subscription->fresh()->last_reminder_day);
    }

    public function test_the_daily_pass_reports_what_it_did(): void
    {
        $this->subscriptionEndingIn(3);

        $this->artisan('subscriptions:refresh')
            ->expectsOutputToContain('Relances envoyées : 1')
            ->assertSuccessful();
    }

    private function subscriptionEndingIn(int $days, string $status = Subscription::STATUS_ACTIVE): Subscription
    {
        $provider = User::factory()->provider()->create(['phone' => '677001122']);
        $subscription = $this->subscribeProvider($provider);

        $subscription->forceFill([
            'status' => $status,
            'ends_at' => now()->addDays($days)->subHour(),
        ])->save();

        return $subscription->fresh();
    }
}
