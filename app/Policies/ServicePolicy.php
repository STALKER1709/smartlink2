<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Services\QuotaService;

class ServicePolicy
{
    public function __construct(private readonly QuotaService $quotas) {}

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Service $service): bool
    {
        return true;
    }

    /**
     * Publier exige un abonnement en cours et de la place dans le palier.
     * Modifier ou supprimer un service déjà publié reste toujours possible :
     * l'expiration masque les services, elle ne confisque pas le compte.
     */
    public function create(User $user): bool
    {
        return $user->isProvider()
            && $user->isActive()
            && $this->quotas->canPublishService($user);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin() || ($user->id === $service->provider_id && $user->isActive());
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin() || $user->id === $service->provider_id;
    }

    public function restore(User $user, Service $service): bool
    {
        return $user->isAdmin() || $user->id === $service->provider_id;
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
