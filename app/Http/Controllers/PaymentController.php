<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\SmsService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rappel de l'opérateur Mobile Money. C'est désormais l'unique canal de
 * facturation de SmartLink : il confirme le règlement d'un abonnement
 * prestataire. Aucun paiement ne circule plus entre client et prestataire.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        $secret = (string) config('campay.webhook_secret');

        // Sans secret configuré, ce point d'entrée créditerait un abonnement à
        // quiconque connaît une référence de paiement — or le payeur lit la
        // sienne à l'écran. Il se ferme donc plutôt que de s'ouvrir à tous.
        if ($secret === '') {
            Log::error('[Campay] Rappel refusé : CAMPAY_WEBHOOK_SECRET n\'est pas configuré.');

            return response()->json(['status' => 'not_configured'], 503);
        }

        $provided = $request->header('X-Campay-Signature') ?? $request->query('token');

        if (! hash_equals($secret, (string) $provided)) {
            Log::warning('[Campay] Webhook signature mismatch', ['ip' => $request->ip()]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $ref = $request->input('external_reference');
        $status = $request->input('status');

        if (empty($ref) || empty($status)) {
            return response()->json(['status' => 'bad_request'], 400);
        }

        $confirmed = DB::transaction(function () use ($request, $ref, $status) {
            $payment = Payment::where('internal_reference', $ref)->lockForUpdate()->first();

            if (! $payment || $payment->status !== Payment::STATUS_PENDING) {
                return null;
            }

            if ($status === 'SUCCESSFUL') {
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'campay_reference' => $request->input('reference'),
                    'paid_at' => now(),
                ]);

                return $payment;
            }

            if (in_array($status, ['FAILED', 'CANCELLED'], true)) {
                $payment->update([
                    'status' => $status === 'CANCELLED'
                        ? Payment::STATUS_CANCELLED
                        : Payment::STATUS_FAILED,
                    'failure_reason' => mb_substr((string) $request->input('message'), 0, 500),
                ]);
            }

            return null;
        });

        if ($confirmed !== null) {
            $this->subscriptions->recordSuccessfulPayment($confirmed);

            $payer = $confirmed->payer;
            if ($payer?->phone) {
                $this->sms->send($payer->phone, __('sms.subscription_renewed', [
                    'amount' => number_format($confirmed->amount_xaf, 0, ',', ' '),
                    'reference' => $confirmed->internal_reference,
                ]));
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
