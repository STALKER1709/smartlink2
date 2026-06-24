<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin() || $this->isParticipant($user, $conversation);
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }

    private function isParticipant(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin() || $user->id === $conversation->client_id || $user->id === $conversation->provider_id;
    }
}
