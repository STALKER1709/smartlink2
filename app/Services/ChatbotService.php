<?php

namespace App\Services;

use App\Contracts\ChatbotProvider;
use App\Models\User;
use App\Services\Ai\AiGate;
use App\Services\Chatbot\RuleBasedChatbotProvider;
use Illuminate\Support\Facades\Log;

/**
 * Point de décision de l'assistant. Le mode par règles n'est jamais retiré :
 * il répond aux visiteurs anonymes, au-delà du quota, au-delà du plafond de
 * dépense, et chaque fois que l'appel à l'IA échoue. L'assistant répond
 * toujours quelque chose — il répond simplement moins bien.
 */
class ChatbotService
{
    public function __construct(
        private readonly ChatbotProvider $provider,
        private readonly RuleBasedChatbotProvider $rules,
        private readonly AiGate $gate,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = [], ?User $user = null): string
    {
        if (! $this->gate->allows($user)) {
            return $this->rules->respond($message, $history, $user);
        }

        try {
            return $this->provider->respond($message, $history, $user);
        } catch (\Throwable $e) {
            Log::error('[IA] Appel à l\'assistant en échec, bascule en mode par règles', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return $this->rules->respond($message, $history, $user);
        }
    }
}
