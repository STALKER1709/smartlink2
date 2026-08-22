@auth
    @if (auth()->user()->isProvider())
        @php
            $subscription = auth()->user()->activeSubscription();
            $quotas = app(\App\Services\QuotaService::class);
            $remaining = $subscription ? $quotas->remainingRequests(auth()->user()) : 0;
        @endphp

        @if (! $subscription)
            <div class="bg-red-50 border-b border-red-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-red-800">
                        <p class="font-semibold">{{ __('ui.subscription.expired') }}</p>
                        <p class="text-red-700">{{ __('ui.subscription.expired_hint') }}</p>
                    </div>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        {{ __('ui.subscription.renew') }}
                    </a>
                </div>
            </div>
        @elseif ($subscription->daysRemaining() <= 7)
            <div class="bg-amber-50 border-b border-amber-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-medium text-amber-900">
                        {{ $subscription->isTrial()
                            ? __('ui.subscription.trial_active', ['days' => $subscription->daysRemaining()])
                            : __('ui.subscription.expires_in', ['days' => $subscription->daysRemaining()]) }}
                    </p>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        {{ $subscription->isTrial() ? __('ui.subscription.subscribe') : __('ui.subscription.renew') }}
                    </a>
                </div>
            </div>
        @elseif ($remaining !== null && $remaining === 0)
            <div class="bg-amber-50 border-b border-amber-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-amber-900">
                        <p class="font-semibold">{{ __('ui.subscription.quota_reached', ['count' => $subscription->plan->max_monthly_requests]) }}</p>
                        <p class="text-amber-800">{{ __('ui.subscription.quota_hidden') }}</p>
                    </div>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        {{ __('ui.subscription.quota_upgrade') }}
                    </a>
                </div>
            </div>
        @endif
    @endif
@endauth
