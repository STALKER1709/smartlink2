<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly CampayService $campay,
        private readonly QuotaService $quotas,
        private readonly SmsService $sms,
    ) {}

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
        $this->quotas->refreshListing($provider->refresh());

        return $subscription;
    }

    /**
     * Engage le règlement d'un palier. Campay n'offrant aucun mandat, le
     * prestataire valide l'opération sur son téléphone : la collecte part en
     * « en attente » et c'est le rappel de l'opérateur qui la confirme.
     *
     * @return array{payment: Payment, status: string, ussd_code: ?string}
     */
    public function requestPayment(User $provider, Plan $plan, string $phone, string $operator): array
    {
        $subscription = $this->subscriptionFor($provider);

        $payment = DB::transaction(function () use ($subscription, $provider, $plan, $phone, $operator) {
            $pending = $subscription->payments()
                ->where('status', Payment::STATUS_PENDING)
                ->where('created_at', '>', now()->subMinutes(10))
                ->lockForUpdate()
                ->get();

            // Une collecte du même palier est déjà en attente sur le téléphone
            // du prestataire : en lancer une seconde le ferait payer deux fois.
            $sameplan = $pending->firstWhere('plan_id', $plan->id);

            if ($sameplan !== null) {
                return $sameplan;
            }

            // Il a changé de palier en cours de route : les collectes en attente
            // pour l'ancien montant sont abandonnées plutôt que réutilisées.
            $pending->each(fn (Payment $stale) => $stale->update([
                'status' => Payment::STATUS_CANCELLED,
                'failure_reason' => 'Abandonnée : changement de palier avant validation.',
            ]));

            return Payment::create([
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'payer_id' => $provider->id,
                'amount_xaf' => $plan->price_xaf,
                'operator' => $operator,
                'phone' => $phone,
                'status' => Payment::STATUS_PENDING,
                'internal_reference' => 'SL-'.strtoupper(Str::random(12)),
            ]);
        });

        $result = $this->campay->collect(
            phone: $phone,
            amountXaf: $plan->price_xaf,
            description: 'Abonnement SmartLink '.$plan->name(),
            externalRef: $payment->internal_reference,
        );

        $status = $result['status'] ?? 'FAILED';

        if ($status === 'SUCCESSFUL') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'campay_reference' => $result['reference'] ?? null,
                'paid_at' => now(),
            ]);

            $this->recordSuccessfulPayment($payment->refresh());
        } elseif ($status !== 'PENDING') {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => mb_substr((string) ($result['error'] ?? 'Paiement non abouti.'), 0, 500),
            ]);
        } else {
            $payment->update(['campay_reference' => $result['reference'] ?? null]);
        }

        return [
            'payment' => $payment->refresh(),
            'status' => $status,
            'ussd_code' => $result['ussd_code'] ?? null,
        ];
    }

    /**
     * L'abonnement que porte le prestataire, quel que soit son état. Un
     * prestataire n'en a jamais qu'un : il est prolongé, jamais dupliqué.
     */
    public function subscriptionFor(User $provider): Subscription
    {
        $existing = $provider->subscriptions()->latest('ends_at')->first();

        if ($existing !== null) {
            return $existing;
        }

        return Subscription::create([
            'user_id' => $provider->id,
            'plan_id' => Plan::query()->active()->orderBy('sort_order')->firstOrFail()->id,
            'status' => Subscription::STATUS_EXPIRED,
            'starts_at' => now(),
            'ends_at' => now(),
        ]);
    }

    /**
     * Relances avant échéance. Sans mandat récurrent, l'abonnement ne se
     * reconduit pas tout seul : le SMS est le seul moyen de prévenir un
     * prestataire avant que ses services ne sortent des recherches.
     *
     * @return int nombre de relances envoyées
     */
    public function sendExpiryReminders(): int
    {
        $thresholds = collect(config('subscription.reminder_days'))
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day > 0)
            ->values();

        if ($thresholds->isEmpty()) {
            return 0;
        }

        $widest = $thresholds->max();

        $sent = 0;

        Subscription::query()
            ->usable()
            ->where('ends_at', '<=', now()->addDays($widest))
            ->with('user')
            ->chunkById(200, function ($subscriptions) use ($thresholds, &$sent) {
                foreach ($subscriptions as $subscription) {
                    $sent += $this->remindOne($subscription, $thresholds) ? 1 : 0;
                }
            });

        return $sent;
    }

    /**
     * @param  Collection<int, int>  $thresholds
     */
    private function remindOne(Subscription $subscription, $thresholds): bool
    {
        $remaining = $subscription->daysRemaining();

        // Parmi les seuils franchis, le plus proche de l'échéance : à 1 jour
        // restant, les seuils 3 et 1 sont tous deux franchis, c'est 1 qui vaut.
        $threshold = $thresholds->filter(fn (int $day) => $remaining <= $day)->min();

        if ($threshold === null) {
            return false;
        }

        // Déjà relancé à ce seuil ou à un seuil plus proche.
        if ($subscription->last_reminder_day !== null && $subscription->last_reminder_day <= $threshold) {
            return false;
        }

        $user = $subscription->user;

        if ($user?->phone === null) {
            return false;
        }

        $this->sms->send($user->phone, __(
            $subscription->isTrial() ? 'sms.trial_ending' : 'sms.subscription_expiring',
            ['days' => max($remaining, 1)],
        ));

        $subscription->forceFill(['last_reminder_day' => $threshold])->save();

        return true;
    }

    /**
     * Bascule en « expiré » les abonnements dont l'échéance est passée.
     * Sans cela, un abonnement échu resterait affiché comme actif : c'est
     * `isUsable()` qui fait autorité, ce passage ne fait qu'aligner le statut.
     */
    public function expireLapsed(): int
    {
        $lapsed = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_TRIALING, Subscription::STATUS_ACTIVE])
            ->where('ends_at', '<=', now())
            ->with('user')
            ->get();

        foreach ($lapsed as $subscription) {
            $subscription->forceFill([
                'status' => Subscription::STATUS_EXPIRED,
                'last_reminder_day' => null,
            ])->save();

            $this->auditLog->log($subscription->user, 'subscription.expired', $subscription);

            if ($subscription->user?->phone !== null) {
                $this->sms->send($subscription->user->phone, __('sms.subscription_expired'));
            }
        }

        return $lapsed->count();
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
                // Le palier réglé prend effet maintenant : un changement de
                // formule ne s'applique qu'une fois le paiement abouti.
                'plan_id' => $payment->plan_id ?? $subscription->plan_id,
                'status' => Subscription::STATUS_ACTIVE,
                'ends_at' => $from->copy()->addDays(config('subscription.cycle_days')),
                'cancelled_at' => null,
                'last_reminder_day' => null,
            ]);

            $this->auditLog->log($payment->payer, 'subscription.renewed', $subscription, [
                'payment_id' => $payment->id,
                'amount_xaf' => $payment->amount_xaf,
                'plan_id' => $subscription->plan_id,
            ]);

            if ($payment->payer !== null) {
                $this->quotas->refreshListing($payment->payer->refresh());
            }

            return $subscription;
        });
    }
}
