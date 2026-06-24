<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Review $review): bool
    {
        if ($review->is_visible) {
            return true;
        }

        return $user && ($user->isAdmin() || $user->id === $review->client_id || $user->id === $review->provider_id);
    }

    public function create(User $user, ?ServiceRequest $serviceRequest = null): bool
    {
        if (! $user->isClient()) {
            return false;
        }

        if ($serviceRequest === null) {
            return true;
        }

        return $user->id === $serviceRequest->client_id
            && $serviceRequest->status === ServiceRequest::STATUS_COMPLETED
            && ! $serviceRequest->review()->exists();
    }

    public function update(User $user, Review $review): bool
    {
        return $user->isAdmin() || $user->id === $review->client_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $user->id === $review->client_id;
    }

    public function restore(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
