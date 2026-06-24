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

    /**
     * Provider-only actions: accept, refuse, start, complete.
     */
    public function respond(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->provider_id && $user->isActive();
    }

    /**
     * Either participant may cancel a request that is not yet final.
     */
    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        return ! $serviceRequest->isFinal()
            && ($user->id === $serviceRequest->client_id || $user->id === $serviceRequest->provider_id);
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
