<?php

namespace App\Services;

use App\Contracts\ChatbotProvider;

class ChatbotService
{
    public function __construct(private readonly ChatbotProvider $provider) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = []): string
    {
        return $this->provider->respond($message, $history);
    }
}
