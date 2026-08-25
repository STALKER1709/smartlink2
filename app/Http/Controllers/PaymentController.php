<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProvider;
use App\Models\Payment;
use App\Services\SmsService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return response()->json(['status' => 'acknowledged']);
        }

        $payment = Payment::query()
            ->where('internal_reference', $event->internalReference)
            ->orWhere('provider_reference', $event->providerReference)
            ->first();

        if ($payment === null || $payment->status !== Payment::STATUS_PENDING) {
            // Rappel sur un paiement inconnu ou déjà tranché : rien à faire,
            // mais on accuse réception pour que le fournisseur cesse de le
            // rejouer.
            return response()->json(['status' => 'ok']);
        }

        // Le statut annoncé dans le rappel n'est jamais cru sur parole : un
        // corps signé prouve l'origine du message, pas l'état courant de la
        // transaction. Seule l'API fait foi.
        $status = $this->provider->status($event->providerReference);

        if ($status === null) {
            // PENDING, HOLD (revue anti-blanchiment) ou lecture en échec :
            // rien de définitif, le paiement reste en attente.
            return response()->json(['status' => 'pending']);
        }

        $confirmed = DB::transaction(function () use ($payment, $status, $event) {
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

            return null;
        });

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

        return response()->json(['status' => 'ok']);
    }
}
