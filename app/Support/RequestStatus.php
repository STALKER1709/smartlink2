<?php

namespace App\Support;

/**
 * Les libellés français des statuts d'une demande.
 *
 * La même table était recopiée dans quatre fichiers — la pastille de statut,
 * le filtre de la liste des demandes, le tableau de bord d'administration — et
 * une cinquième fois nulle part : la notification de changement de statut
 * écrivait la valeur brute de la base, si bien qu'un client lisait
 * « Le statut de votre demande #25 est passé à "in_progress". »
 */
final class RequestStatus
{
    public const DRAFT = 'draft';

    public const SENT = 'sent';

    public const VIEWED = 'viewed';

    public const ACCEPTED = 'accepted';

    public const REFUSED = 'refused';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyée',
            self::VIEWED => 'Vue',
            self::ACCEPTED => 'Acceptée',
            self::REFUSED => 'Refusée',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminée',
            self::CANCELLED => 'Annulée',
        ];
    }

    /**
     * Le libellé, ou la valeur brute si le statut est inconnu — jamais une
     * chaîne vide, qui laisserait un blanc inexplicable dans une phrase.
     */
    public static function label(?string $status): string
    {
        if ($status === null) {
            return '—';
        }

        return self::labels()[$status] ?? $status;
    }
}
