<?php

namespace App\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Ce qu'il faut défaire quand un compte s'en va.
 *
 * La suppression d'un `User` est douce : la ligne reste, donc la cascade des
 * clés étrangères ne part jamais et tout ce qui en dépend — profil, services,
 * demandes, pièce d'identité — survit tel quel. C'est voulu : les demandes et
 * les conversations d'un compte parti restent lisibles par l'autre partie, et
 * les avis déjà laissés continuent de compter pour ceux qui les ont reçus.
 *
 * Mais « rester en base » ne veut pas dire « rester visible ni rester stocké ».
 * Sans ce ménage, un compte supprimé gardait ses services en vitrine et sa
 * pièce d'identité sur le disque, indéfiniment.
 */
class AccountDeletionService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function delete(User $user): void
    {
        $this->auditLog->log($user, 'account.deleted', $user, [
            'role' => $user->role,
        ]);

        DB::transaction(function () use ($user) {
            $this->purgeIdDocument($user);
            $this->retireFromPublicView($user);

            $user->delete();
        });
    }

    /**
     * La pièce d'identité est effacée du disque, pas seulement détachée.
     *
     * Elle n'a plus aucune raison d'exister : la vérification qu'elle servait
     * n'a plus d'objet, et personne ne pense à purger un document que rien ne
     * référence. C'est une donnée d'identité — la garder « au cas où » est
     * précisément ce qu'il ne faut pas faire.
     */
    private function purgeIdDocument(User $user): void
    {
        $profile = $user->providerProfile;

        if ($profile?->id_card_path === null) {
            return;
        }

        Storage::disk(id_documents_disk())->delete($profile->id_card_path);

        $profile->forceFill([
            'id_card_path' => null,
            'id_card_verified' => false,
        ])->save();
    }

    /**
     * Sort le compte des recherches et désactive ses annonces.
     *
     * La garde des contrôleurs refuse déjà une fiche dont le compte n'existe
     * plus ; ceci corrige l'état lui-même, pour que rien ailleurs ne se fie à
     * un `is_listed` resté vrai.
     */
    private function retireFromPublicView(User $user): void
    {
        $user->providerProfile?->forceFill([
            'is_listed' => false,
            'is_promoted' => false,
        ])->save();

        Service::query()
            ->where('provider_id', $user->id)
            ->where('status', Service::STATUS_ACTIVE)
            ->update(['status' => Service::STATUS_INACTIVE]);
    }
}
