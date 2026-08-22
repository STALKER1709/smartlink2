<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_an_admin_sees_the_plans_and_their_subscriber_count(): void
    {
        $plan = Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $this->subscribeProvider($provider, $plan->code);

        $this->actingAs($this->admin)
            ->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee($plan->name())
            ->assertViewHas('plans', fn ($plans) => $plans->firstWhere('id', $plan->id)->active_subscriptions_count === 1);
    }

    public function test_a_price_change_takes_effect_without_a_redeploy(): void
    {
        $plan = Plan::factory()->create(['price_xaf' => 2500]);

        $this->actingAs($this->admin)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['price_xaf' => 3000]))
            ->assertRedirect(route('admin.plans.index'));

        $this->assertSame(3000, $plan->fresh()->price_xaf);
        $this->assertDatabaseHas('audit_logs', ['action' => 'plan.updated']);
    }

    public function test_an_empty_limit_means_unlimited(): void
    {
        $plan = Plan::factory()->create(['max_services' => 3]);

        $this->actingAs($this->admin)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['max_services' => '']))
            ->assertRedirect();

        $this->assertNull($plan->fresh()->max_services);
        $this->assertTrue($plan->fresh()->allowsUnlimitedServices());
    }

    /**
     * Relever un plafond doit rendre visible tout de suite : attendre le
     * passage de nuit laisserait le prestataire invisible sans raison.
     */
    public function test_raising_a_cap_brings_capped_providers_back_immediately(): void
    {
        $plan = Plan::factory()->create(['max_monthly_requests' => 5]);
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $this->subscribeProvider($provider, $plan->code);

        $provider->providerProfile->forceFill([
            'requests_read_count' => 5,
            'requests_read_period' => now()->format('Y-m'),
        ])->save();
        $this->app->make(QuotaService::class)->refreshListing($provider->refresh());

        $this->assertFalse($provider->providerProfile->fresh()->is_listed);

        $this->actingAs($this->admin)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['max_monthly_requests' => 50]))
            ->assertRedirect();

        $this->assertTrue($provider->providerProfile->fresh()->is_listed);
    }

    public function test_withdrawing_a_plan_from_sale_leaves_ongoing_subscriptions_alone(): void
    {
        $plan = Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $this->subscribeProvider($provider, $plan->code);
        Service::factory()->create(['provider_id' => $provider->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['is_active' => '0']))
            ->assertRedirect();

        $this->assertFalse($plan->fresh()->is_active);
        // L'abonnement court jusqu'à son échéance, et le prestataire reste visible.
        $this->assertTrue($provider->fresh()->hasUsableSubscription());
        $this->assertTrue($provider->providerProfile->fresh()->is_listed);

        // Mais le palier n'est plus proposé à la souscription.
        $this->actingAs($provider)
            ->get(route('provider.subscription.checkout', $plan))
            ->assertNotFound();
    }

    public function test_a_negative_price_is_refused(): void
    {
        $plan = Plan::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['price_xaf' => -100]))
            ->assertSessionHasErrors('price_xaf');
    }

    public function test_a_provider_cannot_change_the_prices(): void
    {
        $plan = Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        $this->subscribeProvider($provider, Plan::CODE_PRO);

        $this->actingAs($provider)
            ->put(route('admin.plans.update', $plan), $this->payload($plan, ['price_xaf' => 1]))
            ->assertForbidden();

        $this->actingAs($provider)->get(route('admin.plans.index'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Plan $plan, array $overrides = []): array
    {
        return array_merge([
            'price_xaf' => $plan->price_xaf,
            'max_services' => $plan->max_services,
            'max_monthly_requests' => $plan->max_monthly_requests,
            'is_featured' => $plan->is_featured ? '1' : '0',
            'has_ai_writing' => $plan->has_ai_writing ? '1' : '0',
            'has_stats' => $plan->has_stats ? '1' : '0',
            'is_active' => '1',
        ], $overrides);
    }
}
