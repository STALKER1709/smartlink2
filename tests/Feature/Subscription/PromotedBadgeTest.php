<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotedBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pro_provider_wears_the_badge_everywhere_it_appears(): void
    {
        [$provider, $service] = $this->providerOn(Plan::CODE_PRO);

        $this->get(route('providers.index'))->assertOk()->assertSee(__('ui.plans.promoted_badge'));
        $this->get(route('services.index'))->assertOk()->assertSee(__('ui.plans.promoted_badge'));
        $this->get(route('providers.show', $provider->providerProfile))
            ->assertOk()
            ->assertSee(__('ui.plans.promoted_badge'));
        $this->get(route('services.show', $service))->assertOk()->assertSee(__('ui.plans.promoted_badge'));
    }

    public function test_an_essential_provider_does_not_wear_it(): void
    {
        [$provider, $service] = $this->providerOn(Plan::CODE_ESSENTIAL);

        $this->get(route('providers.index'))->assertOk()->assertDontSee(__('ui.plans.promoted_badge'));
        $this->get(route('services.show', $service))->assertOk()->assertDontSee(__('ui.plans.promoted_badge'));
    }

    public function test_the_badge_falls_with_the_subscription(): void
    {
        [$provider] = $this->providerOn(Plan::CODE_PRO);

        $provider->subscriptions()->update(['ends_at' => now()->subDay()]);
        $this->artisan('subscriptions:refresh')->assertSuccessful();

        $this->assertFalse($provider->providerProfile->fresh()->is_promoted);
    }

    /** @return array{0: User, 1: Service} */
    private function providerOn(string $planCode): array
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'business_name' => 'Atelier Bonamoussadi',
        ]);

        $this->subscribeProvider($provider, $planCode);

        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'title' => 'Depannage plomberie',
            'status' => Service::STATUS_ACTIVE,
        ]);

        $this->app->make(QuotaService::class)->refreshListing($provider->refresh());

        return [$provider, $service];
    }
}
