<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProvider;
use App\Models\Payment;
use App\Services\SmsService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rappel du fournisseur Mobile Money. C'est l'unique canal de facturation de
 * SmartLink : il confirme le règlement d'un abonnement prestataire. Aucun
 * paiement ne circule entre client et prestataire.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly SmsService $sms,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        // Authentification et lecture sont l'affaire du fournisseur : lui seul
        // connaît sa signature et la forme de ses charges utiles. Le contrôleur
        // pose les deux questions séparément parce qu'elles n'appellent pas la
        // même réponse HTTP.
        if (! $this->provider->isAuthentic($request)) {
            return response()->json(['status' => 'rejected'], 403);
        }

        $event = $this->provider->readWebhook($request);

        if ($event === null) {
            // Rappel authentique qui ne parle d'aucun paiement : un test depuis
            // la console du fournisseur, ou un événement dont nous n'avons que
            // faire. Le refuser lui ferait croire que notre point d'entrée est
            // en panne — on en accuse réception.
            return $this->outcome('acknowledged', 'Rappel authentique sans paiement.');
        }

        $payment = Payment::query()
            ->where('internal_reference', $event->internalReference)
            ->orWhere('provider_reference', $event->providerReference)
            ->first();

        if ($payment === null || $payment->status !== Payment::STATUS_PENDING) {
            // Rappel sur un paiement inconnu ou déjà tranché : rien à faire,
            // mais on accuse réception pour que le fournisseur cesse de le
            // rejouer.
            return $this->outcome('ok', $payment === null
                ? 'Aucun paiement ne porte cette référence.'
                : "Paiement déjà tranché : « {$payment->status} ».", $event->internalReference);
        }

        // Le statut annoncé dans le rappel n'est jamais cru sur parole : un
        // corps signé prouve l'origine du message, pas l'état courant de la
        // transaction. Seule l'API fait foi.
        $status = $this->provider->status($event->providerReference);

        if ($status === null) {
            // PENDING, HOLD (revue anti-blanchiment) ou lecture en échec :
            // rien de définitif, le paiement reste en attente.
            return $this->outcome('pending', 'Statut non concluant chez le '
                .'fournisseur : le paiement reste en attente.', $event->internalReference);
        }

        $tranche = DB::transaction(function () use ($payment, $status, $event) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            if ($locked === null || $locked->status !== Payment::STATUS_PENDING) {
                return null;
            }

            if ($status === 'success') {
                $locked->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'provider_reference' => $event->providerReference,
                    'paid_at' => now(),
                ]);

                return $locked;
            }

            $locked->update([
                'status' => Payment::STATUS_FAILED,
                'provider_reference' => $event->providerReference,
                'failure_reason' => 'Refusé par l\'opérateur.',
            ]);

            return $locked;
        });

        /*
         * Le paiement rendu par la transaction est celui que *ce* rappel a
         * tranché — succès comme refus. `null` veut dire qu'il n'a rien
         * tranché : un rappel concurrent était passé avant lui.
         */
        $confirmed = $tranche?->status === Payment::STATUS_SUCCESS ? $tranche : null;

        if ($confirmed !== null) {
            $this->subscriptions->recordSuccessfulPayment($confirmed);

            $payer = $confirmed->payer;

            if ($payer?->phone !== null) {
                $this->sms->send($payer->phone, __('sms.subscription_renewed', [
                    'amount' => number_format($confirmed->amount_xaf, 0, ',', ' '),
                    'reference' => $confirmed->internal_reference,
                ]));
            }
        }

        /*
         * Le refus se dit aussi, et c'est le message qui compte le plus.
         *
         * Le rappel arrive après coup : le prestataire a quitté la page depuis
         * longtemps, et rien à l'écran ne lui apprendra que son règlement a
         * été refusé. Sans ce SMS, il se croit à jour et découvre la coupure
         * en constatant qu'il ne reçoit plus de demandes — sans jamais faire
         * le lien.
         */
        if ($tranche?->status === Payment::STATUS_FAILED && $tranche->payer?->phone !== null) {
            $this->sms->send($tranche->payer->phone, __('sms.subscription_failed', [
                'amount' => number_format($tranche->amount_xaf, 0, ',', ' '),
            ]));
        }

        // Trois issues, à ne pas confondre dans la trace : créditée, refusée,
        // ou déjà tranchée par un rappel concurrent — ce dernier cas se
        // reconnaît à ce que le fournisseur dit « réglé » alors que la
        // transaction n'a rien eu à mettre à jour.
        return $this->outcome('ok', match (true) {
            $confirmed !== null => 'Règlement confirmé, abonnement crédité.',
            $status === 'success' => 'Déjà tranché par un rappel concurrent.',
            default => 'Règlement refusé par l\'opérateur.',
        }, $event->internalReference);
    }

    /**
     * Toute issue du rappel laisse une trace, y compris — surtout — le succès.
     *
     * Les journaux d'accès de l'hébergeur ne montrent que le code HTTP, et il
     * vaut 200 pour quatre issues très différentes. Sans cette ligne, la seule
     * façon de savoir si un abonnement a été crédité serait d'aller regarder
     * en base.
     */
    private function outcome(string $status, string $detail, ?string $reference = null): JsonResponse
    {
        Log::info('[Paiement] Rappel · '.$status.' · '.$detail
            .($reference !== null ? ' · référence: '.$reference : ''));

        return response()->json(['status' => $status]);
    }
}
