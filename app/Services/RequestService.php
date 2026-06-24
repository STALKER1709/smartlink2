<?php

namespace App\Services;

use App\Exceptions\InvalidRequestTransitionException;
use App\Models\Conversation;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestService
{
    /**
     * Allowed status transitions: from => [allowed to...].
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        ServiceRequest::STATUS_DRAFT => [ServiceRequest::STATUS_SENT, ServiceRequest::STATUS_CANCELLED],
        ServiceRequest::STATUS_SENT => [ServiceRequest::STATUS_VIEWED, ServiceRequest::STATUS_ACCEPTED, ServiceRequest::STATUS_REFUSED, ServiceRequest::STATUS_CANCELLED],
        ServiceRequest::STATUS_VIEWED => [ServiceRequest::STATUS_ACCEPTED, ServiceRequest::STATUS_REFUSED, ServiceRequest::STATUS_CANCELLED],
        ServiceRequest::STATUS_ACCEPTED => [ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_CANCELLED],
        ServiceRequest::STATUS_IN_PROGRESS => [ServiceRequest::STATUS_COMPLETED, ServiceRequest::STATUS_CANCELLED],
        ServiceRequest::STATUS_REFUSED => [],
        ServiceRequest::STATUS_COMPLETED => [],
        ServiceRequest::STATUS_CANCELLED => [],
    ];

    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $client, array $data): ServiceRequest
    {
        $status = ($data['as_draft'] ?? false) ? ServiceRequest::STATUS_DRAFT : ServiceRequest::STATUS_SENT;

        $request = ServiceRequest::create([
            'client_id' => $client->id,
            'provider_id' => $data['provider_id'],
            'service_id' => $data['service_id'] ?? null,
            'message' => $data['message'],
            'preferred_date' => $data['preferred_date'] ?? null,
            'status' => $status,
        ]);

        $this->recordHistory($request, null, $status, $client, null);
        $this->auditLog->log($client, 'request.created', $request);

        if ($status === ServiceRequest::STATUS_SENT) {
            $this->notifications->notifyNewRequest($request);
        }

        return $request;
    }

    public function send(ServiceRequest $request, User $client): ServiceRequest
    {
        $this->transition($request, ServiceRequest::STATUS_SENT, $client);
        $this->notifications->notifyNewRequest($request);

        return $request;
    }

    public function markViewed(ServiceRequest $request, User $provider): ServiceRequest
    {
        if ($request->status !== ServiceRequest::STATUS_SENT) {
            return $request;
        }

        return $this->transition($request, ServiceRequest::STATUS_VIEWED, $provider);
    }

    public function accept(ServiceRequest $request, User $provider): ServiceRequest
    {
        $from = $request->status;
        $this->transition($request, ServiceRequest::STATUS_ACCEPTED, $provider, attributes: ['responded_at' => now()]);

        Conversation::firstOrCreate([
            'client_id' => $request->client_id,
            'provider_id' => $request->provider_id,
            'request_id' => $request->id,
        ]);

        $this->notifications->notifyStatusChanged($request, $provider, $from);

        return $request;
    }

    public function refuse(ServiceRequest $request, User $provider, ?string $reason = null): ServiceRequest
    {
        $from = $request->status;
        $this->transition(
            $request,
            ServiceRequest::STATUS_REFUSED,
            $provider,
            $reason,
            ['responded_at' => now(), 'cancellation_reason' => $reason],
        );
        $this->notifications->notifyStatusChanged($request, $provider, $from);

        return $request;
    }

    public function start(ServiceRequest $request, User $provider): ServiceRequest
    {
        $from = $request->status;
        $this->transition($request, ServiceRequest::STATUS_IN_PROGRESS, $provider);
        $this->notifications->notifyStatusChanged($request, $provider, $from);

        return $request;
    }

    public function complete(ServiceRequest $request, User $provider): ServiceRequest
    {
        $from = $request->status;
        $this->transition($request, ServiceRequest::STATUS_COMPLETED, $provider, attributes: ['completed_at' => now()]);
        $this->notifications->notifyStatusChanged($request, $provider, $from);

        return $request;
    }

    public function cancel(ServiceRequest $request, User $actor, ?string $reason = null): ServiceRequest
    {
        $from = $request->status;
        $this->transition($request, ServiceRequest::STATUS_CANCELLED, $actor, $reason, ['cancellation_reason' => $reason]);
        $this->notifications->notifyStatusChanged($request, $actor, $from);

        return $request;
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidRequestTransitionException
     */
    private function transition(
        ServiceRequest $request,
        string $to,
        ?User $actor,
        ?string $note = null,
        array $attributes = [],
    ): ServiceRequest {
        $from = $request->status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new InvalidRequestTransitionException(
                "Transition de statut non autorisée : \"{$from}\" vers \"{$to}\"."
            );
        }

        return DB::transaction(function () use ($request, $from, $to, $actor, $note, $attributes) {
            $request->forceFill(array_merge($attributes, ['status' => $to]))->save();
            $this->recordHistory($request, $from, $to, $actor, $note);
            $this->auditLog->log($actor, 'request.status_changed', $request, ['from' => $from, 'to' => $to]);

            return $request;
        });
    }

    private function recordHistory(
        ServiceRequest $request,
        ?string $from,
        string $to,
        ?User $actor,
        ?string $note,
    ): RequestStatusHistory {
        return RequestStatusHistory::create([
            'request_id' => $request->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor?->id,
            'note' => $note,
        ]);
    }
}
