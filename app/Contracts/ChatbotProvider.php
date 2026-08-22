<?php

namespace App\Contracts;

use App\Models\User;

interface ChatbotProvider
{
    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  User|null  $user  utilisateur connecté, pour le décompte de consommation
     */
    public function respond(string $message, array $history = [], ?User $user = null): string;
}
