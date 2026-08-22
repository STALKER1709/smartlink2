<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\QuotaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(private readonly QuotaService $quotas) {}

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
}
