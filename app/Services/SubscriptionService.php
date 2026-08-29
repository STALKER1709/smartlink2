<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\CollectionResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PaymentProvider $payments,
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
     * Active un palier sans contrepartie financière.
     *
     * Il ne passe pas par `requestPayment` : un encaissement de 0 FCFA n'a
     * pas de sens et serait refusé par l'opérateur. Deux refus le protègent
     * d'un mauvais usage — un palier payant ne peut pas être activé par ici,
     * et un abonnement encore en cours ne peut pas être remplacé par lui.
     *
     * Ce second refus n'est pas une contrainte technique mais un garde-fou :
     * basculer sur le palier gratuit alors qu'il reste vingt jours réglés
     * détruirait ce qui a été payé, sans que rien ne le rende au prestataire.
     *
     * @return array{subscription: ?Subscription, error: ?string}
     */
    public function activateFreePlan(User $provider, Plan $plan): array
    {
        if (! $provider->isProvider()) {
            return ['subscription' => null, 'error' => 'not_provider'];
        }

        if (! $plan->isFree() || ! $plan->is_active) {
            return ['subscription' => null, 'error' => 'not_free'];
        }

        $subscription = $this->subscriptionFor($provider);

        if ($subscription->isUsable() && $subscription->plan_id !== $plan->id) {
            return ['subscription' => $subscription, 'error' => 'still_running'];
        }

        $subscription->update([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => $subscription->starts_at ?? now(),
            'ends_at' => now()->addDays(config('subscription.cycle_days')),
            'last_reminder_day' => null,
        ]);

        $this->auditLog->log($provider, 'subscription.free_activated', $subscription, [
            'plan_id' => $plan->id,
        ]);

        // Ramène immédiatement les services au plafond du palier gratuit :
        // sans cela, tout ce qui a été publié pendant l'essai resterait
        // visible jusqu'au passage quotidien.
        $this->quotas->refreshListing($provider->refresh());

        return ['subscription' => $subscription->refresh(), 'error' => null];
    }

    /**
     * Engage le règlement d'un palier. Le Mobile Money n'offrant aucun mandat
     * récurrent, le prestataire valide l'opération sur son téléphone : la
     * collecte part « en attente » et c'est le rappel de l'opérateur qui la
     * confirme.
     *
     * @return array{payment: Payment, status: string}
     */
    public function requestPayment(User $provider, Plan $plan, string $phone, string $operator): array
    {
        if ($plan->isFree()) {
            throw new \InvalidArgumentException(
                'Un palier gratuit ne s\'encaisse pas : passer par activateFreePlan().'
            );
        }

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
            //
            // La référence du fournisseur est libérée au passage. Elle est
            // unique en base, et rien ne garantit que la collecte suivante en
            // reçoive une différente : la garder sur une collecte abandonnée
            // ferait échouer l'enregistrement de la nouvelle. On la conserve en
            // clair dans le motif, qui n'est là que pour la trace. Un rappel
            // tardif sur cette collecte reste rattachable : le webhook cherche
            // d'abord par référence interne, qui, elle, ne bouge pas.
            $pending->each(fn (Payment $stale) => $stale->update([
                'status' => Payment::STATUS_CANCELLED,
                'provider_reference' => null,
                'failure_reason' => 'Abandonnée : changement de palier avant validation.'
                    .($stale->provider_reference !== null ? ' Référence opérateur : '.$stale->provider_reference.'.' : ''),
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

        $result = $this->payments->collect(
            phone: $phone,
            operator: $operator,
            amountXaf: $plan->price_xaf,
            description: 'Abonnement SmartLink '.$plan->name(),
            reference: $payment->internal_reference,
        );

        if ($result->providerReference !== null) {
            $payment->update(['provider_reference' => $result->providerReference]);
        }

        if ($result->status === CollectionResult::STATUS_SUCCESS) {
            $payment->update(['status' => Payment::STATUS_SUCCESS, 'paid_at' => now()]);
            $this->recordSuccessfulPayment($payment->refresh());

            // `recordSuccessfulPayment` rafraîchit le payeur qu'il a chargé
            // par la relation ; l'instance reçue ici est celle de la requête,
            // et c'est elle qui portera l'affichage. Sans cet oubli, elle
            // rendrait encore l'ancien palier.
            $provider->forgetActiveSubscription();
        } elseif ($result->status === CollectionResult::STATUS_FAILED) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => mb_substr($result->error ?? 'Paiement non abouti.', 0, 500),
            ]);
        }

        return [
            'payment' => $payment->refresh(),
            'status' => $result->status,
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

        $gratuit = Plan::freePlan();

        Subscription::query()
            ->usable()
            ->where('ends_at', '<=', now()->addDays($widest))
            // Un abonnement gratuit se reconduit tout seul : le relancer
            // reviendrait à réclamer un règlement qui n'existe pas.
            ->when($gratuit !== null, fn ($q) => $q->where('plan_id', '!=', $gratuit->id))
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

        $gratuit = Plan::freePlan();
        $expires = 0;

        foreach ($lapsed as $subscription) {
            // Un abonnement gratuit ne s'éteint pas : il se reconduit. Le
            // laisser expirer sortirait le prestataire des recherches tous
            // les trente jours sans qu'il ait rien à régler, et l'y ferait
            // rentrer par un formulaire de paiement à 0 FCFA.
            if ($gratuit !== null && $subscription->plan_id === $gratuit->id) {
                $subscription->forceFill([
                    'status' => Subscription::STATUS_ACTIVE,
                    'ends_at' => now()->addDays(config('subscription.cycle_days')),
                    'last_reminder_day' => null,
                ])->save();

                continue;
            }

            $subscription->forceFill([
                'status' => Subscription::STATUS_EXPIRED,
                'last_reminder_day' => null,
            ])->save();

            $expires++;
            $this->auditLog->log($subscription->user, 'subscription.expired', $subscription);

            if ($subscription->user?->phone !== null) {
                $this->sms->send($subscription->user->phone, __('sms.subscription_expired'));
            }
        }

        // Les reconductions gratuites ne comptent pas : le nombre annoncé est
        // celui des prestataires qui viennent de sortir des recherches.
        return $expires;
    }

    /**
     * Un paiement abouti prolonge l'abonnement d'un cycle. Si l'échéance est
     * encore devant, le cycle s'ajoute à la fin ; sinon il repart de maintenant.
     *
     * Un règlement ne crédite qu'une fois. Le statut « success » dit que
     * l'argent est arrivé, pas qu'un cycle a été accordé : c'est `credited_at`
     * qui porte cette réponse, posé dans la même transaction que la
     * prolongation. Sans lui, rejouer un règlement offrait trente jours.
     */
    public function recordSuccessfulPayment(Payment $payment): Subscription
    {
        return DB::transaction(function () use ($payment) {
            $verrouille = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $subscription = $payment->subscription()->lockForUpdate()->firstOrFail();

            if ($verrouille->credited_at !== null) {
                return $subscription;
            }

            $from = $subscription->ends_at !== null && $subscription->ends_at->isFuture()
                ? $subscription->ends_at
                : now();

            $verrouille->forceFill(['credited_at' => now()])->save();

            $subscription->update([
                // Le palier réglé prend effet maintenant : un changement de
                // formule ne s'applique qu'une fois le paiement abouti.
                'plan_id' => $payment->plan_id ?? $subscription->plan_id,
                'status' => Subscription::STATUS_ACTIVE,
                'ends_at' => $from->copy()->addDays(config('subscription.cycle_days')),
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
