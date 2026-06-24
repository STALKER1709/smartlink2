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
