<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewRequestNotification;
use App\Notifications\NewReviewNotification;
use App\Notifications\RequestStatusChangedNotification;

class NotificationService
{
    public function __construct(private readonly SmsService $sms) {}

    public function notifyNewRequest(ServiceRequest $request): void
    {
        $provider = $request->provider;
        $provider?->notify(new NewRequestNotification($request));

        if ($provider?->phone) {
            $this->sms->send($provider->phone, __('sms.request_received'));
        }
    }

    public function notifyStatusChanged(ServiceRequest $request, User $actor, string $from): void
    {
        $recipient = $actor->id === $request->client_id ? $request->provider : $request->client;
        $recipient?->notify(new RequestStatusChangedNotification($request, $from, $request->status));

        $smsKey = match ($request->status) {
            ServiceRequest::STATUS_ACCEPTED => 'sms.request_accepted',
            ServiceRequest::STATUS_REFUSED => 'sms.request_refused',
            ServiceRequest::STATUS_COMPLETED => 'sms.request_completed',
            ServiceRequest::STATUS_CANCELLED => 'sms.request_cancelled',
            default => null,
        };

        if ($smsKey && $recipient?->phone) {
            $this->sms->send($recipient->phone, __($smsKey));
        }
    }

    public function notifyNewMessage(Message $message): void
    {
        $conversation = $message->conversation;
        $recipient = $conversation->otherParticipant($message->sender);
        $recipient->notify(new NewMessageNotification($message));

        if ($recipient->phone) {
            $this->sms->send($recipient->phone, __('sms.new_message'));
        }
    }

    public function notifyNewReview(Review $review): void
    {
        $review->provider?->notify(new NewReviewNotification($review));
    }
}
