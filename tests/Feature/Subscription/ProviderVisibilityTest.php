<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $quotas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotas = $this->app->make(QuotaService::class);
    }

    public function test_a_subscribed_provider_appears_in_search_and_on_the_home_page(): void
    {
        [$provider, $service] = $this->providerWithService();

        $this->get(route('services.index'))->assertOk()->assertSee($service->title);
        $this->get(route('providers.index'))->assertOk()->assertSee($provider->providerProfile->business_name);
        $this->get(route('home'))->assertOk()->assertSee($service->title);
    }

    public function test_an_expired_provider_disappears_from_search(): void
    {
        [$provider, $service] = $this->providerWithService();

        $provider->subscriptions()->update([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subDay(),
        ]);
        $this->quotas->refreshListing($provider->refresh());

        $this->get(route('services.index'))->assertOk()->assertDontSee($service->title);
        $this->get(route('providers.index'))->assertOk()->assertDontSee($provider->providerProfile->business_name);
        $this->get(route('home'))->assertOk()->assertDontSee($service->title);
    }

    public function test_an_expired_provider_is_no_longer_reachable_by_direct_link(): void
    {
        [$provider, $service] = $this->providerWithService();
        $this->expire($provider);

        $this->get(route('services.show', $service))->assertNotFound();
        $this->get(route('providers.show', $provider->providerProfile))->assertNotFound();
    }

    public function test_the_provider_itself_still_reaches_its_own_hidden_page(): void
    {
        [$provider, $service] = $this->providerWithService();
        $this->expire($provider);

        $this->actingAs($provider)->get(route('services.show', $service))->assertOk();
        $this->actingAs($provider)->get(route('providers.show', $provider->providerProfile))->assertOk();
    }

    public function test_an_admin_still_reaches_a_hidden_provider_page(): void
    {
        [$provider, $service] = $this->providerWithService();
        $this->expire($provider);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('services.show', $service))->assertOk();
    }

    public function test_a_provider_at_the_monthly_cap_disappears_from_search(): void
    {
        [$provider, $service] = $this->providerWithService();

        $provider->providerProfile->forceFill([
            'requests_read_count' => $provider->currentPlan()->max_monthly_requests,
            'requests_read_period' => now()->format('Y-m'),
        ])->save();
        $this->quotas->refreshListing($provider->refresh());

        $this->get(route('services.index'))->assertOk()->assertDontSee($service->title);
    }

    public function test_the_daily_pass_restores_visibility_when_the_month_turns(): void
    {
        [$provider, $service] = $this->providerWithService();

        $provider->providerProfile->forceFill([
            'requests_read_count' => $provider->currentPlan()->max_monthly_requests,
            'requests_read_period' => now()->format('Y-m'),
        ])->save();
        $this->quotas->refreshListing($provider->refresh());

        $this->get(route('services.index'))->assertDontSee($service->title);

        $this->travel(1)->months();
        $this->artisan('subscriptions:refresh')->assertSuccessful();

        $this->get(route('services.index'))->assertOk()->assertSee($service->title);
    }

    public function test_the_daily_pass_marks_lapsed_subscriptions_as_expired(): void
    {
        [$provider] = $this->providerWithService();

        $provider->subscriptions()->update(['ends_at' => now()->subDay()]);

        $this->artisan('subscriptions:refresh')->assertSuccessful();

        $this->assertSame(
            Subscription::STATUS_EXPIRED,
            $provider->subscriptions()->first()->status,
        );
        $this->assertFalse($provider->providerProfile->fresh()->is_listed);
    }

    /** @return array{0: User, 1: Service} */
    private function providerWithService(): array
    {
        $plan = Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'business_name' => 'Atelier Bonamoussadi',
        ]);

        Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'ends_at' => now()->addYear(),
        ]);

        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'title' => 'Reparation de fuite sous evier',
            'status' => Service::STATUS_ACTIVE,
            'is_available' => true,
        ]);

        $this->quotas->refreshListing($provider->refresh());

        return [$provider, $service];
    }

    private function expire(User $provider): void
    {
        $provider->subscriptions()->update([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subDay(),
        ]);

        $this->quotas->refreshListing($provider->refresh());
    }
}
