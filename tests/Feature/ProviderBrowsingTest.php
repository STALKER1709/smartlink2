<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_providers(): void
    {
        ProviderProfile::factory(3)->create();

        $response = $this->get(route('providers.index'));

        $response->assertOk();
        $response->assertViewHas('providers', fn ($providers) => $providers->total() === 3);
    }

    public function test_providers_can_be_filtered_by_verified_only(): void
    {
        ProviderProfile::factory()->verified()->create();
        ProviderProfile::factory()->create();

        $response = $this->get(route('providers.index', ['verified_only' => 1]));

        $response->assertViewHas('providers', fn ($providers) => $providers->total() === 1);
    }

    public function test_guest_can_view_a_provider_profile_with_services_and_reviews(): void
    {
        $providerProfile = ProviderProfile::factory()->create();
        $service = Service::factory()->create(['provider_id' => $providerProfile->user_id]);

        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->completed()->create([
            'client_id' => $client->id,
        ]);
        Review::create([
            'request_id' => $serviceRequest->id,
            'client_id' => $client->id,
            'provider_id' => $providerProfile->user_id,
            'rating' => 5,
            'comment' => 'Excellent travail.',
        ]);

        $response = $this->get(route('providers.show', $providerProfile));

        $response->assertOk();
        $response->assertViewHas('services', fn ($services) => $services->pluck('id')->contains($service->id));
        $response->assertViewHas('reviews', fn ($reviews) => $reviews->pluck('comment')->contains('Excellent travail.'));
    }
}
