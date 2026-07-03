<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Services\CampayService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CampayService $campay,
        private readonly SmsService $sms,
    ) {}

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->authorize('view', $serviceRequest);

        $existingDeposit = $serviceRequest->payments()
            ->where('type', Payment::TYPE_DEPOSIT)
            ->where('status', Payment::STATUS_SUCCESS)
            ->first();

        return view('payment.show', [
            'serviceRequest' => $serviceRequest,
            'existingDeposit' => $existingDeposit,
            'depositAmount' => $this->depositAmount($serviceRequest),
        ]);
    }

    public function initiate(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('view', $serviceRequest);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'operator' => ['required', 'in:mtn,orange'],
        ]);

        $amount = $this->depositAmount($serviceRequest);

        $internalRef = 'SL-'.strtoupper(Str::random(12));

        $payment = DB::transaction(function () use ($serviceRequest, $request, $validated, $amount, $internalRef) {
            return Payment::create([
                'request_id' => $serviceRequest->id,
                'payer_id' => $request->user()->id,
                'amount_xaf' => $amount,
                'operator' => $validated['operator'],
                'phone' => $validated['phone'],
                'type' => Payment::TYPE_DEPOSIT,
                'status' => Payment::STATUS_PENDING,
                'internal_reference' => $internalRef,
            ]);
        });

        $result = $this->campay->collect(
            phone: $validated['phone'],
            amountXaf: $amount,
            description: 'Acompte demande #'.$serviceRequest->id.' — SmartLink',
            externalRef: $internalRef,
        );

        if (($result['status'] ?? '') === 'SUCCESSFUL') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'campay_reference' => $result['reference'] ?? null,
                'paid_at' => now(),
            ]);

            $this->sms->send(
                $request->user()->phone,
                __('sms.payment_success', [
                    'amount' => number_format($amount, 0, ',', ' '),
                    'reference' => $internalRef,
                ])
            );

            return redirect()->route('requests.show', $serviceRequest)
                ->with('status', 'Paiement réussi ! Référence : '.$internalRef);
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'failure_reason' => $result['error'] ?? 'Échec du paiement',
        ]);

        return back()->withErrors(['payment' => $result['error'] ?? 'Paiement non abouti. Veuillez réessayer.']);
    }

    public function webhook(Request $request): JsonResponse
    {
        $ref = $request->input('external_reference');
        $status = $request->input('status');

        $payment = Payment::where('internal_reference', $ref)->first();

        if (! $payment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            return response()->json(['status' => 'already_processed']);
        }

        if ($status === 'SUCCESSFUL') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'campay_reference' => $request->input('reference'),
                'paid_at' => now(),
            ]);

            $payer = $payment->payer;
            if ($payer) {
                $this->sms->send(
                    $payer->phone,
                    __('sms.payment_success', [
                        'amount' => number_format($payment->amount_xaf, 0, ',', ' '),
                        'reference' => $payment->internal_reference,
                    ])
                );
            }
        } elseif (in_array($status, ['FAILED', 'CANCELLED'], true)) {
            $payment->update([
                'status' => strtolower($status) === 'cancelled'
                    ? Payment::STATUS_CANCELLED
                    : Payment::STATUS_FAILED,
                'failure_reason' => $request->input('message'),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function depositAmount(ServiceRequest $serviceRequest): int
    {
        $price = (int) ($serviceRequest->service?->price_amount ?? 0);
        return $price > 0 ? (int) round($price * 0.25) : 5000;
    }
}
