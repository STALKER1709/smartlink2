<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Ouvre l'essai gratuit à l'inscription d'un prestataire : les droits du
     * palier le plus complet, pour la durée configurée, sans paiement.
     */
    public function startTrial(User $provider): ?Subscription
    {
        if (! $provider->isProvider() || $provider->subscriptions()->exists()) {
            return null;
        }

        $plan = Plan::trialPlan();

        if ($plan === null) {
            return null;
        }

        $subscription = Subscription::create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_TRIALING,
            'starts_at' => now(),
            'ends_at' => now()->addDays(config('subscription.trial_days')),
        ]);

        $this->auditLog->log($provider, 'subscription.trial_started', $subscription);

        return $subscription;
    }

    /**
     * Bascule en « expiré » les abonnements dont l'échéance est passée.
     * Sans cela, un abonnement échu resterait affiché comme actif : c'est
     * `isUsable()` qui fait autorité, ce passage ne fait qu'aligner le statut.
     */
    public function expireLapsed(): int
    {
        return Subscription::query()
            ->whereIn('status', [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])
            ->where('ends_at', '<=', now())
            ->update([
                'status' => Subscription::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);
    }

    /**
     * Un paiement abouti prolonge l'abonnement d'un cycle. Si l'échéance est
     * encore devant, le cycle s'ajoute à la fin ; sinon il repart de maintenant.
     */
    public function recordSuccessfulPayment(Payment $payment): Subscription
    {
        return DB::transaction(function () use ($payment) {
            $subscription = $payment->subscription()->lockForUpdate()->firstOrFail();

            $from = $subscription->ends_at !== null && $subscription->ends_at->isFuture()
                ? $subscription->ends_at
                : now();

            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'ends_at' => $from->copy()->addDays(config('subscription.cycle_days')),
                'cancelled_at' => null,
            ]);

            $this->auditLog->log($payment->payer, 'subscription.renewed', $subscription, [
                'payment_id' => $payment->id,
                'amount_xaf' => $payment->amount_xaf,
            ]);

            return $subscription;
        });
    }
}
