<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\User;

/**
 * Tient le compte de ce que l'IA consomme : par utilisateur pour le quota
 * quotidien, et globalement pour le plafond de dépense mensuel.
 */
class AiUsageRecorder
{
    public function record(
        ?User $user,
        string $feature,
        string $model,
        int $inputTokens,
        int $outputTokens,
    ): AiUsage {
        return AiUsage::create([
            'user_id' => $user?->id,
            'feature' => $feature,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd' => $this->cost($model, $inputTokens, $outputTokens),
        ]);
    }

    /**
     * Coût en dollars, d'après les tarifs par million de jetons. Un modèle
     * absent de la grille est compté à zéro : mieux vaut un plafond trop
     * permissif qu'un plafond qui bloque la plateforme sur une clé inconnue.
     */
    public function cost(string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = config('ai.pricing')[$model] ?? null;

        if ($pricing === null) {
            return 0.0;
        }

        return round(
            ($inputTokens / 1_000_000) * $pricing['input']
            + ($outputTokens / 1_000_000) * $pricing['output'],
            6,
        );
    }

    public function messagesToday(User $user): int
    {
        return AiUsage::query()
            ->where('user_id', $user->id)
            ->where('feature', AiUsage::FEATURE_CHAT)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function spentThisMonthUsd(): float
    {
        return (float) AiUsage::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost_usd');
    }
}
