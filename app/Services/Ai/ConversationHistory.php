<?php

namespace App\Services\Ai;

/**
 * Met l'historique fourni par le navigateur en état d'être envoyé à l'API :
 * il vient du client, donc rien n'y est digne de confiance.
 */
class ConversationHistory
{
    /**
     * @param  array<int, mixed>  $history
     * @return array<int, array{role: string, content: string}>
     */
    public function prepare(array $history, ?int $turns = null): array
    {
        $turns ??= (int) config('ai.history_turns');

        if ($turns <= 0) {
            return [];
        }

        $clean = [];

        foreach ($history as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $role = $entry['role'] ?? null;
            $content = $entry['content'] ?? null;

            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            if (! is_string($content) || trim($content) === '') {
                continue;
            }

            // Deux tours consécutifs du même rôle ne peuvent pas venir d'une
            // vraie conversation : on garde le plus récent.
            if ($clean !== [] && end($clean)['role'] === $role) {
                array_pop($clean);
            }

            $clean[] = ['role' => $role, 'content' => trim($content)];
        }

        $clean = array_slice($clean, -$turns);

        // L'API exige que la conversation commence par un tour utilisateur.
        while ($clean !== [] && $clean[0]['role'] !== 'user') {
            array_shift($clean);
        }

        return array_values($clean);
    }
}
