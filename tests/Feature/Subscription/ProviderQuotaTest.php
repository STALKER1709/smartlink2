<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderQuotaTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $quotas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotas = $this->app->make(QuotaService::class);
    }

    public function test_a_provider_at_the_service_cap_cannot_publish_another(): void
    {
        // Palier Essentiel : 3 services au maximum.
        [$provider, $plan] = $this->subscribedProvider();
        $category = ServiceCategory::factory()->create();

        Service::factory()->count($plan->max_services)->create(['provider_id' => $provider->id]);

        $this->assertFalse($this->quotas->canPublishService($provider));

        $this->actingAs($provider)
            ->get(route('provider.services.create'))
            ->assertForbidden();

        $this->actingAs($provider)->post(route('provider.services.store'), [
            'title' => 'Un service de trop',
            'category_id' => $category->id,
            'description' => 'Une description suffisamment longue pour passer la validation.',
            'city' => 'Douala',
        ])->assertForbidden();

        $this->assertSame($plan->max_services, $provider->services()->count());
    }

    public function test_a_provider_below_the_service_cap_can_still_publish(): void
    {
        [$provider, $plan] = $this->subscribedProvider();

        Service::factory()->count($plan->max_services - 1)->create(['provider_id' => $provider->id]);

        $this->assertTrue($this->quotas->canPublishService($provider));
        $this->actingAs($provider)->get(route('provider.services.create'))->assertOk();
    }

    public function test_a_provider_without_a_subscription_cannot_publish_but_keeps_its_services(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $service = Service::factory()->create(['provider_id' => $provider->id]);

        $this->assertFalse($this->quotas->canPublishService($provider));
        $this->actingAs($provider)->get(route('provider.services.create'))->assertForbidden();

        // Le compte n'est pas confisqué : il modifie encore ce qu'il a publié.
        $this->actingAs($provider)->get(route('provider.services.edit', $service))->assertOk();
    }

    public function test_reading_a_request_consumes_one_slot_of_the_monthly_cap(): void
    {
        [$provider] = $this->subscribedProvider();
        $request = $this->incomingRequest($provider);

        $this->actingAs($provider)->get(route('requests.show', $request))->assertOk();

        $this->assertSame(1, $this->quotas->requestsRead($provider));
        $this->assertSame(ServiceRequest::STATUS_VIEWED, $request->fresh()->status);
    }

    public function test_reopening_a_request_does_not_consume_a_second_slot(): void
    {
        [$provider] = $this->subscribedProvider();
        $request = $this->incomingRequest($provider);

        $this->actingAs($provider)->get(route('requests.show', $request))->assertOk();
        $this->actingAs($provider)->get(route('requests.show', $request))->assertOk();

        $this->assertSame(1, $this->quotas->requestsRead($provider));
    }

    public function test_at_the_monthly_cap_a_new_request_is_locked_and_the_provider_leaves_search(): void
    {
        [$provider, $plan] = $this->subscribedProvider();

        $this->exhaustRequestQuota($provider, $plan->max_monthly_requests);

        $request = $this->incomingRequest($provider);

        $this->actingAs($provider)
            ->get(route('requests.show', $request))
            ->assertOk()
            ->assertViewIs('requests.locked');

        // La demande n'est pas consommée : elle reste à lire une fois payé.
        $this->assertSame(ServiceRequest::STATUS_SENT, $request->fresh()->status);
        $this->assertFalse($provider->providerProfile->fresh()->is_listed);
    }

    public function test_a_request_already_open_stays_readable_once_the_cap_is_reached(): void
    {
        [$provider, $plan] = $this->subscribedProvider();
        $request = $this->incomingRequest($provider);

        $this->actingAs($provider)->get(route('requests.show', $request))->assertOk();
        $this->exhaustRequestQuota($provider, $plan->max_monthly_requests);

        $this->actingAs($provider)
            ->get(route('requests.show', $request))
            ->assertOk()
            ->assertViewIs('requests.show');
    }

    public function test_the_counter_starts_over_when_the_month_changes(): void
    {
        [$provider, $plan] = $this->subscribedProvider();
        $this->exhaustRequestQuota($provider, $plan->max_monthly_requests);

        $this->assertFalse($this->quotas->hasRequestQuotaLeft($provider));

        $this->travel(1)->months();

        $this->assertSame(0, $this->quotas->requestsRead($provider));
        $this->assertTrue($this->quotas->hasRequestQuotaLeft($provider));
    }

    /** @return array{0: User, 1: Plan} */
    private function subscribedProvider(bool $pro = false): array
    {
        $plan = $pro ? Plan::factory()->pro()->create() : Plan::factory()->create();
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            // Assez long pour que les tests de bascule mensuelle portent bien
            // sur le plafond de demandes, et non sur l'échéance.
            'ends_at' => now()->addYear(),
        ]);

        $this->quotas->refreshListing($provider);

        return [$provider->refresh(), $plan];
    }

    private function incomingRequest(User $provider): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'provider_id' => $provider->id,
            'client_id' => User::factory()->client()->create()->id,
            'status' => ServiceRequest::STATUS_SENT,
        ]);
    }

    private function exhaustRequestQuota(User $provider, int $cap): void
    {
        $provider->providerProfile->forceFill([
            'requests_read_count' => $cap,
            'requests_read_period' => now()->format('Y-m'),
        ])->save();

        $this->quotas->refreshListing($provider->refresh());
    }
}
