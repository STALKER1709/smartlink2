<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Support\RequestStatus;
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
     * La charge utile porte les données brutes *et* la phrase.
     *
     * La phrase, parce qu'elle est ce qui s'affiche ; les données, parce
     * qu'elles permettent à la vue de recomposer la phrase pour les
     * notifications déjà en base — celles-ci disaient « est passé à
     * "in_progress" », la valeur de la colonne, à un lecteur français.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request.status_changed',
            'request_id' => $this->request->id,
            'service_title' => $this->request->service?->title,
            'from_status' => $this->from,
            'to_status' => $this->to,
            'message' => self::phrase($this->request->service?->title, $this->to),
        ];
    }

    /**
     * « Votre demande « Peinture intérieure » est acceptée. » — le service
     * plutôt que son numéro : c'est ainsi que le client la reconnaît.
     */
    public static function phrase(?string $serviceTitle, ?string $status): string
    {
        $objet = $serviceTitle === null
            ? 'Votre demande'
            : 'Votre demande « '.$serviceTitle.' »';

        return $objet.' est désormais : '.mb_strtolower(RequestStatus::label($status)).'.';
    }
}
