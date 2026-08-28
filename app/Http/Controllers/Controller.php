<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Un prestataire retiré des recherches — abonnement échu ou plafond mensuel
     * atteint — ne doit pas rester joignable par lien direct : un client y
     * enverrait une demande qui ne serait jamais lue. Le prestataire lui-même
     * et les administrateurs gardent l'accès à la fiche.
     */
    protected function abortIfProviderIsHidden(int $providerId): void
    {
        /*
         * Le compte doit d'abord exister, et ce contrôle passe avant celui de
         * l'administrateur.
         *
         * La suppression d'un compte est douce : la cascade des clés étrangères
         * ne part jamais, donc le profil et les services restent en base, et
         * `is_listed` y garde la valeur qu'il avait. La garde ci-dessous
         * laissait alors passer, puis la vue déréférençait un prestataire que
         * la relation rend `null` — la fiche répondait 500 au lieu de 404, sur
         * une URL publique et indexable.
         */
        if (! User::whereKey($providerId)->exists()) {
            abort(404);
        }

        $viewer = request()->user();

        if ($viewer !== null && ($viewer->id === $providerId || $viewer->isAdmin())) {
            return;
        }

        $listed = ProviderProfile::query()
            ->where('user_id', $providerId)
            ->value('is_listed');

        abort_unless((bool) $listed, 404);
    }
}
