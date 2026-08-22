<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Décide si un message part vers l'IA ou reste au mode par règles. Chaque
 * refus rabat sur les règles plutôt que sur une erreur : l'assistant répond
 * toujours, il répond simplement moins bien.
 */
class AiGate
{
    public const REASON_ALLOWED = 'allowed';

    public const REASON_DRIVER_OFF = 'driver_off';

    public const REASON_NO_KEY = 'no_key';

    public const REASON_GUEST = 'guest';

    public const REASON_DAILY_QUOTA = 'daily_quota';

    public const REASON_BUDGET = 'budget';

    public function __construct(private readonly AiUsageRecorder $usage) {}

    public function allows(?User $user, string $feature = AiUsage::FEATURE_CHAT): bool
    {
        return $this->decide($user, $feature) === self::REASON_ALLOWED;
    }

    public function decide(?User $user, string $feature = AiUsage::FEATURE_CHAT): string
    {
        if (config('ai.driver') !== 'claude') {
            return self::REASON_DRIVER_OFF;
        }

        if (empty(config('ai.api_key'))) {
            return self::REASON_NO_KEY;
        }

        // Les visiteurs anonymes gardent le mode par règles, sauf pour les
        // fonctions explicitement ouvertes : la dépense devient un motif
        // d'inscription plutôt qu'une charge ouverte.
        if ($user === null
            && config('ai.limits.require_authentication')
            && ! $this->isOpenToGuests($feature)) {
            return self::REASON_GUEST;
        }

        $budget = (float) config('ai.limits.monthly_budget_usd');

        if ($budget > 0 && $this->usage->spentThisMonthUsd() >= $budget) {
            Log::warning('[IA] Plafond mensuel atteint, bascule en mode par règles', [
                'budget_usd' => $budget,
            ]);

            return self::REASON_BUDGET;
        }

        // Le quota quotidien ne borne que la conversation : c'est elle qui
        // coûte cher. L'extraction de recherche reste bornée par le plafond
        // mensuel, sans quoi un utilisateur bavard ne pourrait plus chercher.
        $daily = (int) config('ai.limits.daily_messages_per_user');

        if ($feature === AiUsage::FEATURE_CHAT
            && $user !== null
            && $daily > 0
            && $this->usage->messagesToday($user) >= $daily) {
            return self::REASON_DAILY_QUOTA;
        }

        return self::REASON_ALLOWED;
    }

    public function isOpenToGuests(string $feature): bool
    {
        return in_array($feature, config('ai.limits.guest_features', []), true);
    }
}
