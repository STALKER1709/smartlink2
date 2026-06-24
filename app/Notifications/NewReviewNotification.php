<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification
{
    public function __construct(private readonly Review $review)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review.new',
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'message' => 'Vous avez reçu un nouvel avis.',
        ];
    }
}
