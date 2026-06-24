<?php

namespace Tests\Unit\Services;

use App\Exceptions\InvalidRequestTransitionException;
use App\Models\Conversation;
use App\Models\RequestStatusHistory;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use App\Notifications\RequestStatusChangedNotification;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private RequestService $requests;

    private User $client;

    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requests = app(RequestService::class);
        $this->client = User::factory()->client()->create();
        $this->provider = User::factory()->provider()->create();
    }

    public function test_create_as_draft_does_not_notify_provider(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Bonjour, êtes-vous disponible ?',
            'as_draft' => true,
        ]);

        $this->assertSame(ServiceRequest::STATUS_DRAFT, $request->status);
        Notification::assertNothingSent();
        $this->assertSame(1, RequestStatusHistory::where('request_id', $request->id)->count());
    }

    public function test_create_as_sent_notifies_provider(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Bonjour, êtes-vous disponible ?',
        ]);

        $this->assertSame(ServiceRequest::STATUS_SENT, $request->status);
        Notification::assertSentTo($this->provider, NewRequestNotification::class);
    }

    public function test_send_transitions_draft_to_sent(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande en brouillon.',
            'as_draft' => true,
        ]);

        $this->requests->send($request, $this->client);

        $this->assertSame(ServiceRequest::STATUS_SENT, $request->status);
        Notification::assertSentTo($this->provider, NewRequestNotification::class);
    }

    public function test_mark_viewed_is_noop_unless_status_is_sent(): void
    {
        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Brouillon.',
            'as_draft' => true,
        ]);

        $this->requests->markViewed($request, $this->provider);

        $this->assertSame(ServiceRequest::STATUS_DRAFT, $request->status);
    }

    public function test_mark_viewed_transitions_sent_to_viewed(): void
    {
        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->markViewed($request, $this->provider);

        $this->assertSame(ServiceRequest::STATUS_VIEWED, $request->status);
    }

    public function test_accept_creates_conversation_and_notifies_client(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->accept($request, $this->provider);

        $this->assertSame(ServiceRequest::STATUS_ACCEPTED, $request->status);
        $this->assertNotNull($request->responded_at);
        $this->assertDatabaseHas('conversations', [
            'client_id' => $this->client->id,
            'provider_id' => $this->provider->id,
            'request_id' => $request->id,
        ]);
        $this->assertSame(1, Conversation::where('request_id', $request->id)->count());
        Notification::assertSentTo($this->client, RequestStatusChangedNotification::class);
    }

    public function test_refuse_records_reason_and_notifies_client(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->refuse($request, $this->provider, 'Indisponible sur la période demandée.');

        $this->assertSame(ServiceRequest::STATUS_REFUSED, $request->status);
        $this->assertSame('Indisponible sur la période demandée.', $request->cancellation_reason);
        Notification::assertSentTo($this->client, RequestStatusChangedNotification::class);
    }

    public function test_full_lifecycle_to_completion(): void
    {
        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->accept($request, $this->provider);
        $this->requests->start($request, $this->provider);
        $this->assertSame(ServiceRequest::STATUS_IN_PROGRESS, $request->status);

        $this->requests->complete($request, $this->provider);
        $this->assertSame(ServiceRequest::STATUS_COMPLETED, $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertTrue($request->isFinal());
    }

    public function test_cancel_by_client_records_reason(): void
    {
        Notification::fake();

        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->cancel($request, $this->client, "Je n'ai plus besoin de cette prestation.");

        $this->assertSame(ServiceRequest::STATUS_CANCELLED, $request->status);
        $this->assertSame("Je n'ai plus besoin de cette prestation.", $request->cancellation_reason);
        Notification::assertSentTo($this->provider, RequestStatusChangedNotification::class);
    }

    public function test_invalid_transition_throws_exception(): void
    {
        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->accept($request, $this->provider);

        $this->expectException(InvalidRequestTransitionException::class);

        $this->requests->refuse($request, $this->provider);
    }

    public function test_transition_from_final_status_throws_exception(): void
    {
        $request = $this->requests->create($this->client, [
            'provider_id' => $this->provider->id,
            'message' => 'Demande.',
        ]);

        $this->requests->accept($request, $this->provider);
        $this->requests->start($request, $this->provider);
        $this->requests->complete($request, $this->provider);

        $this->expectException(InvalidRequestTransitionException::class);

        $this->requests->cancel($request, $this->client);
    }

    public function test_request_can_target_a_service_and_inherits_its_provider(): void
    {
        $service = Service::factory()->create(['provider_id' => $this->provider->id]);

        $request = $this->requests->create($this->client, [
            'provider_id' => $service->provider_id,
            'service_id' => $service->id,
            'message' => 'Demande pour ce service.',
        ]);

        $this->assertSame($service->id, $request->service_id);
        $this->assertSame($this->provider->id, $request->provider_id);
    }
}
