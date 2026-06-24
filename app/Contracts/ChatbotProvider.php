<?php

namespace App\Contracts;

interface ChatbotProvider
{
    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function respond(string $message, array $history = []): string;
}
