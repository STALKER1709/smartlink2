<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provider_sees_its_plan_its_usage_and_the_available_plans(): void
    {
        Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        $this->subscribeProvider($provider, Plan::CODE_PRO);
        Service::factory()->count(2)->create(['provider_id' => $provider->id]);

        $this->actingAs($provider)
            ->get(route('provider.subscription.show'))
            ->assertOk()
            ->assertViewIs('provider.subscription.show')
            ->assertViewHas('servicesUsed', 2)
            ->assertViewHas('requestsRead', 0)
            ->assertSee(__('ui.plans.essential.name'))
            ->assertSee(__('ui.plans.pro.name'));
    }

    public function test_an_expired_provider_sees_the_expiry_notice(): void
    {
        $provider = User::factory()->provider()->create();
        $this->subscribeProvider($provider);
        $provider->subscriptions()->update([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($provider)
            ->get(route('provider.subscription.show'))
            ->assertOk()
            ->assertViewHas('subscription', null)
            ->assertSee(__('ui.subscription.expired'));
    }

    public function test_a_client_cannot_reach_the_provider_subscription_page(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->get(route('provider.subscription.show'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('provider.subscription.show'))->assertRedirect(route('login'));
    }
}
