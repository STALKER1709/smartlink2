<?php

namespace App\Policies;

use App\Models\ProviderProfile;
use App\Models\User;

class ProviderProfilePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ProviderProfile $providerProfile): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isProvider();
    }

    public function update(User $user, ProviderProfile $providerProfile): bool
    {
        return $user->isAdmin() || $user->id === $providerProfile->user_id;
    }

    /**
     * Qui peut lire la pièce d'identité déposée.
     *
     * L'administrateur, parce qu'il doit la vérifier ; le prestataire, parce
     * que c'est la sienne et qu'il doit pouvoir contrôler ce qu'il a envoyé.
     * Personne d'autre — pas même un autre prestataire, pas même un client
     * qui a travaillé avec lui.
     *
     * C'est le seul contrôle qui protège ces documents : ils ne sont plus
     * joignables par une URL publique.
     */
    public function viewIdDocument(User $user, ProviderProfile $providerProfile): bool
    {
        return $user->isAdmin() || $user->id === $providerProfile->user_id;
    }

    public function delete(User $user, ProviderProfile $providerProfile): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ProviderProfile $providerProfile): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ProviderProfile $providerProfile): bool
    {
        return $user->isAdmin();
    }
}
