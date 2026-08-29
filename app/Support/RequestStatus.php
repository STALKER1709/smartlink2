<?php

namespace App\Support;

/**
 * Les libellés des statuts d'une demande.
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
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::SENT,
            self::VIEWED,
            self::ACCEPTED,
            self::REFUSED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        /*
         * Les libellés passent par les traductions : ce sont les mots que lit
         * le client sur sa demande, et la bascule de langue de la barre de
         * navigation ne vaut rien si la pastille de statut reste en français.
         */
        return array_combine(
            self::all(),
            array_map(fn (string $statut) => __('ui.status.'.$statut), self::all()),
        );
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
