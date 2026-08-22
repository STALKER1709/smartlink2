<?php

namespace App\Services\Chatbot;

use Anthropic\Client;
use App\Contracts\ChatbotProvider;
use App\Models\AiUsage;
use App\Models\User;
use App\Services\Ai\AiUsageRecorder;
use App\Services\Ai\ConversationHistory;
use App\Services\Ai\SmartLinkContext;

class ClaudeChatbotProvider implements ChatbotProvider
{
    public function __construct(
        private readonly Client $client,
        private readonly SmartLinkContext $context,
        private readonly AiUsageRecorder $usage,
        private readonly ConversationHistory $history,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function respond(string $message, array $history = [], ?User $user = null): string
    {
        $model = config('ai.models.chat');

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: 1024,
            system: [[
                'type' => 'text',
                'text' => $this->context->systemPrompt(),
                // Le contexte est stable d'un appel à l'autre : le mettre en
                // cache rend les tours suivants nettement moins coûteux.
                'cacheControl' => ['type' => 'ephemeral'],
            ]],
            messages: [...$this->history->prepare($history), ['role' => 'user', 'content' => $message]],
        );

        $this->usage->record(
            user: $user,
            feature: AiUsage::FEATURE_CHAT,
            model: $model,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
        );

        return $this->firstText($response->content);
    }

    /**
     * @param  array<int, mixed>  $content
     */
    private function firstText(array $content): string
    {
        foreach ($content as $block) {
            if (($block->type ?? null) === 'text' && trim($block->text) !== '') {
                return trim($block->text);
            }
        }

        throw new \RuntimeException('Réponse Claude sans bloc de texte exploitable.');
    }
}
