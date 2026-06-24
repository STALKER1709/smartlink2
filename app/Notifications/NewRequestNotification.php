<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification
{
    public function __construct(private readonly ServiceRequest $request)
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
            'type' => 'request.new',
            'request_id' => $this->request->id,
            'client_id' => $this->request->client_id,
            'message' => 'Vous avez reçu une nouvelle demande.',
        ];
    }
}
