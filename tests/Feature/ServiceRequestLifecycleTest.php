<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $provider;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->client()->create();
        $this->provider = User::factory()->provider()->create();
        $this->subscribeProvider($this->provider);
        $this->service = Service::factory()->create(['provider_id' => $this->provider->id]);
    }

    public function test_guest_is_redirected_to_login_when_creating_a_request(): void
    {
        $response = $this->post(route('requests.store'), [
            'service_id' => $this->service->id,
            'message' => 'Bonjour.',
            'action' => 'send',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_client_can_send_a_request_for_a_service(): void
    {
        $response = $this->actingAs($this->client)->post(route('requests.store'), [
            'service_id' => $this->service->id,
            'message' => 'Bonjour, êtes-vous disponible cette semaine ?',
            'action' => 'send',
        ]);

        $serviceRequest = ServiceRequest::first();
        $response->assertRedirect(route('requests.show', $serviceRequest));
        $this->assertSame(ServiceRequest::STATUS_SENT, $serviceRequest->status);
        $this->assertSame($this->provider->id, $serviceRequest->provider_id);
        $this->assertSame($this->client->id, $serviceRequest->client_id);
    }

    public function test_client_can_save_a_request_as_draft(): void
    {
        $this->actingAs($this->client)->post(route('requests.store'), [
            'provider_id' => $this->provider->id,
            'message' => 'Brouillon.',
            'action' => 'draft',
        ]);

        $this->assertSame(ServiceRequest::STATUS_DRAFT, ServiceRequest::first()->status);
    }

    public function test_request_requires_a_service_or_a_provider(): void
    {
        $response = $this->actingAs($this->client)->post(route('requests.store'), [
            'message' => 'Bonjour.',
            'action' => 'send',
        ]);

        $response->assertSessionHasErrors(['service_id', 'provider_id']);
    }

    public function test_request_for_inactive_service_fails_validation(): void
    {
        $inactive = Service::factory()->inactive()->create();

        $response = $this->actingAs($this->client)->post(route('requests.store'), [
            'service_id' => $inactive->id,
            'message' => 'Bonjour.',
            'action' => 'send',
        ]);

        $response->assertSessionHasErrors('service_id');
    }

    public function test_provider_cannot_create_a_request(): void
    {
        $otherProvider = User::factory()->provider()->create();

        $response = $this->actingAs($otherProvider)->post(route('requests.store'), [
            'service_id' => $this->service->id,
            'message' => 'Bonjour.',
            'action' => 'send',
        ]);

        $response->assertForbidden();
    }

    public function test_viewing_a_sent_request_marks_it_as_viewed_for_the_provider(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->provider)->get(route('requests.show', $serviceRequest));

        $this->assertSame(ServiceRequest::STATUS_VIEWED, $serviceRequest->refresh()->status);
    }

    public function test_strangers_cannot_view_someone_elses_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);
        $stranger = User::factory()->client()->create();

        $response = $this->actingAs($stranger)->get(route('requests.show', $serviceRequest));

        $response->assertForbidden();
    }

    public function test_provider_can_accept_a_request_and_a_conversation_is_opened(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->provider)->post(route('requests.accept', $serviceRequest));

        $response->assertRedirect();
        $this->assertSame(ServiceRequest::STATUS_ACCEPTED, $serviceRequest->refresh()->status);
        $this->assertDatabaseHas('conversations', ['request_id' => $serviceRequest->id]);
    }

    public function test_client_cannot_accept_their_own_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->client)->post(route('requests.accept', $serviceRequest));

        $response->assertForbidden();
    }

    public function test_provider_can_refuse_a_request_with_a_reason(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->provider)->post(route('requests.refuse', $serviceRequest), [
            'reason' => 'Indisponible.',
        ]);

        $serviceRequest->refresh();
        $this->assertSame(ServiceRequest::STATUS_REFUSED, $serviceRequest->status);
        $this->assertSame('Indisponible.', $serviceRequest->cancellation_reason);
    }

    public function test_provider_can_start_and_complete_an_accepted_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->accepted()->create([
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->provider)->post(route('requests.start', $serviceRequest));
        $this->assertSame(ServiceRequest::STATUS_IN_PROGRESS, $serviceRequest->refresh()->status);

        $this->actingAs($this->provider)->post(route('requests.complete', $serviceRequest));
        $this->assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->refresh()->status);
    }

    public function test_either_party_can_cancel_a_non_final_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
        ]);

        $this->actingAs($this->client)->post(route('requests.cancel', $serviceRequest), [
            'reason' => "Je n'ai plus besoin.",
        ]);

        $this->assertSame(ServiceRequest::STATUS_CANCELLED, $serviceRequest->refresh()->status);
    }

    public function test_cannot_cancel_a_completed_request(): void
    {
        $serviceRequest = ServiceRequest::factory()->forService($this->service)->completed()->create([
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->client)->post(route('requests.cancel', $serviceRequest));

        $response->assertForbidden();
    }

    public function test_client_requests_index_only_lists_their_own_requests(): void
    {
        ServiceRequest::factory()->forService($this->service)->create(['client_id' => $this->client->id]);
        ServiceRequest::factory()->create();

        $response = $this->actingAs($this->client)->get(route('requests.index'));

        $response->assertViewHas('requests', fn ($requests) => $requests->total() === 1);
    }

    public function test_provider_requests_index_can_be_filtered_by_status(): void
    {
        ServiceRequest::factory()->forService($this->service)->create([
            'client_id' => $this->client->id,
            'status' => ServiceRequest::STATUS_SENT,
        ]);
        ServiceRequest::factory()->forService($this->service)->refused()->create([
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->provider)->get(route('requests.index', ['status' => ServiceRequest::STATUS_REFUSED]));

        $response->assertViewHas('requests', fn ($requests) => $requests->total() === 1);
    }
}
