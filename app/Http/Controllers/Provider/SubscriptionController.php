<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\SubscribeRequest;
use App\Models\Plan;
use App\Services\Payment\CollectionResult;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotas,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function show(Request $request): View
    {
        $provider = $request->user();

        return view('provider.subscription.show', [
            'subscription' => $provider->activeSubscription(),
            'plans' => Plan::query()->active()->orderBy('sort_order')->get(),
            'servicesUsed' => $this->quotas->servicesUsed($provider),
            'requestsRead' => $this->quotas->requestsRead($provider),
            'remainingRequests' => $this->quotas->remainingRequests($provider),
        ]);
    }

    public function checkout(Request $request, Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        return view('provider.subscription.checkout', [
            'plan' => $plan,
            'subscription' => $request->user()->activeSubscription(),
        ]);
    }

    public function subscribe(SubscribeRequest $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $validated = $request->validated();

        $result = $this->subscriptions->requestPayment(
            provider: $request->user(),
            plan: $plan,
            phone: $validated['phone'],
            operator: $validated['operator'],
        );

        return match ($result['status']) {
            CollectionResult::STATUS_SUCCESS => redirect()
                ->route('provider.subscription.show')
                ->with('status', __('ui.subscription.payment_confirmed', [
                    'plan' => $plan->name(),
                    'reference' => $result['payment']->internal_reference,
                ])),

            // Le prestataire doit encore composer son code sur le téléphone :
            // le rappel de l'opérateur confirmera, ou non.
            CollectionResult::STATUS_PENDING => redirect()
                ->route('provider.subscription.show')
                ->with('status', __('ui.subscription.payment_pending', [
                    'reference' => $result['payment']->internal_reference,
                ])),

            default => back()
                ->withInput()
                ->withErrors(['phone' => $result['payment']->failure_reason ?: __('ui.payment.failed')]),
        };
    }
}
