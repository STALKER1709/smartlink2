@auth
    @if (auth()->user()->isProvider())
        @php
            $subscription = auth()->user()->activeSubscription();
            $quotas = app(\App\Services\QuotaService::class);
            $remaining = $subscription ? $quotas->remainingRequests(auth()->user()) : 0;
        @endphp

        @if (! $subscription)
            <div class="bg-error-container border-b border-outline-variant">
                <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-on-error-container">
                        <p class="font-semibold">{{ __('ui.subscription.expired') }}</p>
                        <p>{{ __('ui.subscription.expired_hint') }}</p>
                    </div>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-full bg-error px-4 py-2 text-sm font-button-text font-semibold text-on-error hover:opacity-90 transition-opacity">
                        {{ __('ui.subscription.renew') }}
                    </a>
                </div>
            </div>
        @elseif ($subscription->daysRemaining() <= 7)
            <div class="bg-tertiary-container/15 border-b border-outline-variant">
                <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-medium text-tertiary">
                        {{ $subscription->isTrial()
                            ? __('ui.subscription.trial_active', ['days' => $subscription->daysRemaining()])
                            : __('ui.subscription.expires_in', ['days' => $subscription->daysRemaining()]) }}
                    </p>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-full bg-tertiary px-4 py-2 text-sm font-button-text font-semibold text-on-tertiary hover:opacity-90 transition-opacity">
                        {{ $subscription->isTrial() ? __('ui.subscription.subscribe') : __('ui.subscription.renew') }}
                    </a>
                </div>
            </div>
        @elseif ($remaining !== null && $remaining === 0)
            <div class="bg-tertiary-container/15 border-b border-outline-variant">
                <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-tertiary">
                        <p class="font-semibold">{{ __('ui.subscription.quota_reached', ['count' => $subscription->plan->max_monthly_requests]) }}</p>
                        <p>{{ __('ui.subscription.quota_hidden') }}</p>
                    </div>
                    <a href="{{ route('provider.subscription.show') }}" class="shrink-0 rounded-full bg-tertiary px-4 py-2 text-sm font-button-text font-semibold text-on-tertiary hover:opacity-90 transition-opacity">
                        {{ __('ui.subscription.quota_upgrade') }}
                    </a>
                </div>
            </div>
        @endif
    @endif
@endauth
