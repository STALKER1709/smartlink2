<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_review_a_completed_request(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->completed()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client)->post(route('requests.review', $serviceRequest), [
            'rating' => 5,
            'comment' => 'Très professionnel.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'request_id' => $serviceRequest->id,
            'rating' => 5,
            'comment' => 'Très professionnel.',
        ]);
    }

    public function test_review_updates_provider_rating_average(): void
    {
        $provider = User::factory()->provider()->create();
        $provider->providerProfile()->save(
            ProviderProfile::factory()->make(['user_id' => $provider->id])
        );
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->completed()->create([
            'client_id' => $client->id,
        ]);

        $this->actingAs($client)->post(route('requests.review', $serviceRequest), [
            'rating' => 4,
            'comment' => 'Bon travail.',
        ]);

        $provider->providerProfile->refresh();
        $this->assertSame('4.00', $provider->providerProfile->rating_avg);
        $this->assertSame(1, $provider->providerProfile->rating_count);
    }

    public function test_cannot_review_a_request_that_is_not_completed(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->accepted()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client)->post(route('requests.review', $serviceRequest), [
            'rating' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_review_the_same_request_twice(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->completed()->create([
            'client_id' => $client->id,
        ]);

        $this->actingAs($client)->post(route('requests.review', $serviceRequest), ['rating' => 5]);
        $response = $this->actingAs($client)->post(route('requests.review', $serviceRequest), ['rating' => 3]);

        $response->assertForbidden();
        $this->assertSame(1, Review::where('request_id', $serviceRequest->id)->count());
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $client = User::factory()->client()->create();
        $serviceRequest = ServiceRequest::factory()->forService($service)->completed()->create([
            'client_id' => $client->id,
        ]);

        $response = $this->actingAs($client)->post(route('requests.review', $serviceRequest), [
            'rating' => 6,
        ]);

        $response->assertSessionHasErrors('rating');
    }
}
