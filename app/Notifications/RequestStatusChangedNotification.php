<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Notifications\Notification;

class RequestStatusChangedNotification extends Notification
{
    public function __construct(
        private readonly ServiceRequest $request,
        private readonly string $from,
        private readonly string $to,
    ) {}

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
            'type' => 'request.status_changed',
            'request_id' => $this->request->id,
            'from_status' => $this->from,
            'to_status' => $this->to,
            'message' => "Le statut de votre demande #{$this->request->id} est passé à \"{$this->to}\".",
        ];
    }
}
