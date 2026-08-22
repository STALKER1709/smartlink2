<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialOnRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create();
        Plan::factory()->pro()->create();
    }

    public function test_a_new_provider_starts_a_trial_and_is_immediately_visible(): void
    {
        $this->post(route('register'), [
            'name' => 'Jean-Paul Etoo',
            'email' => 'jp@example.cm',
            'phone' => '677112233',
            'role' => User::ROLE_PROVIDER,
            'business_name' => 'Jean-Paul Plomberie',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertRedirect(route('dashboard', absolute: false));

        $provider = User::where('email', 'jp@example.cm')->firstOrFail();
        $subscription = $provider->activeSubscription();

        $this->assertNotNull($subscription);
        $this->assertSame(Subscription::STATUS_TRIALING, $subscription->status);
        $this->assertSame(Plan::CODE_PRO, $subscription->plan->code);
        $this->assertTrue($provider->providerProfile->fresh()->is_listed);
    }

    public function test_a_new_client_gets_no_subscription(): void
    {
        $this->post(route('register'), [
            'name' => 'Aicha Mballa',
            'email' => 'aicha@example.cm',
            'phone' => '699112233',
            'role' => User::ROLE_CLIENT,
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ]);

        $client = User::where('email', 'aicha@example.cm')->firstOrFail();

        $this->assertFalse($client->hasUsableSubscription());
        $this->assertSame(0, Subscription::count());
    }
}
