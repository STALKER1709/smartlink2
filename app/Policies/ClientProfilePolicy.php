<?php

namespace App\Policies;

use App\Models\ClientProfile;
use App\Models\User;

class ClientProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ClientProfile $clientProfile): bool
    {
        return $user->isAdmin() || $user->id === $clientProfile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    public function update(User $user, ClientProfile $clientProfile): bool
    {
        return $user->isAdmin() || $user->id === $clientProfile->user_id;
    }

    public function delete(User $user, ClientProfile $clientProfile): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ClientProfile $clientProfile): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ClientProfile $clientProfile): bool
    {
        return $user->isAdmin();
    }
}
