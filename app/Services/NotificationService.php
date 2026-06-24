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
    public function notifyNewRequest(ServiceRequest $request): void
    {
        $request->provider?->notify(new NewRequestNotification($request));
    }

    public function notifyStatusChanged(ServiceRequest $request, User $actor, string $from): void
    {
        $recipient = $actor->id === $request->client_id ? $request->provider : $request->client;

        $recipient?->notify(new RequestStatusChangedNotification($request, $from, $request->status));
    }

    public function notifyNewMessage(Message $message): void
    {
        $conversation = $message->conversation;
        $conversation->otherParticipant($message->sender)->notify(new NewMessageNotification($message));
    }

    public function notifyNewReview(Review $review): void
    {
        $review->provider?->notify(new NewReviewNotification($review));
    }
}
