<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin()
            || $user->id === $serviceRequest->client_id
            || $user->id === $serviceRequest->provider_id;
    }

    public function create(User $user): bool
    {
        return $user->isClient() && $user->isActive();
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->client_id || $user->id === $serviceRequest->provider_id;
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }
}
