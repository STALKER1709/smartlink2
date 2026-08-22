<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
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
