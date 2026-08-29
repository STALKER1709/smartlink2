<x-app-layout :titre="__('Mon abonnement')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.nav.subscription')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8 space-y-8">

        {{-- État courant --}}
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            @if ($subscription)
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-label-md text-on-surface-variant">{{ __('ui.subscription.current_plan') }}</p>
                        <p class="font-headline-lg text-headline-lg text-on-surface">{{ $subscription->plan->name() }}</p>
                        <p class="text-label-md text-on-surface-variant mt-1">
                            {{ $subscription->isTrial()
                                ? __('ui.subscription.trial_active', ['days' => $subscription->daysRemaining()])
                                : __('ui.subscription.expires_in', ['days' => $subscription->daysRemaining()]) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-label-numeric text-headline-md text-on-surface">
                            {{ $subscription->isTrial() ? '0 FCFA' : $subscription->plan->formattedPrice() }}
                        </p>
                        <p class="text-label-md text-on-surface-variant">{{ __('ui.plans.per_month') }}</p>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-outline-variant pt-6">
                    @php
                        $auPlafond = ! $subscription->plan->allowsUnlimitedServices()
                            && $servicesUsed >= $subscription->plan->max_services;
                    @endphp
                    <div>
                        <dt class="text-label-md text-on-surface-variant">{{ __('ui.subscription.services_used') }}</dt>
                        <dd class="font-label-numeric text-body-lg text-on-surface">
                            {{ $servicesUsed }}
                            @if (! $subscription->plan->allowsUnlimitedServices())
                                <span class="text-on-surface-variant">/ {{ $subscription->plan->max_services }}</span>
                            @endif
                        </dd>
                        {{-- « 3 / 3 » en gris ne dit pas qu'on ne peut plus
                             rien publier. C'est pourtant sur cet écran que la
                             réponse se trouve. --}}
                        @if ($auPlafond)
                            <p class="mt-0.5 text-label-sm font-medium text-tertiary">{{ __('ui.subscription.cap_reached') }}</p>
                        @endif
                    </div>
                    <div>
                        <dt class="text-label-md text-on-surface-variant">{{ __('ui.subscription.requests_used') }}</dt>
                        <dd class="font-label-numeric text-body-lg text-on-surface">
                            {{ $requestsRead }}
                            @if (! $subscription->plan->allowsUnlimitedRequests())
                                <span class="text-on-surface-variant">/ {{ $subscription->plan->max_monthly_requests }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            @else
                <p class="font-headline-md text-headline-md text-error">{{ __('ui.subscription.expired') }}</p>
                <p class="text-label-md text-on-surface-variant mt-1">{{ __('ui.subscription.expired_hint') }}</p>
            @endif
        </div>

        {{-- Paliers --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($plans as $plan)
                @php $isCurrent = $subscription && $subscription->plan_id === $plan->id; @endphp
                <div @class([
                    'bg-surface-container-lowest rounded-xl border p-6 flex flex-col',
                    'border-primary ring-1 ring-primary' => $isCurrent,
                    'border-outline-variant' => ! $isCurrent,
                ])>
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-headline-md text-headline-md text-on-surface">{{ $plan->name() }}</h3>
                        @if ($isCurrent)
                            <span class="rounded-full bg-primary-container/20 px-2.5 py-0.5 text-label-sm font-semibold text-primary">
                                {{ __('ui.subscription.current_plan') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-label-md text-on-surface-variant mt-1">{{ $plan->tagline() }}</p>

                    <p class="mt-4 font-label-numeric text-headline-lg text-on-surface">
                        {{ $plan->formattedPrice() }}
                        <span class="text-body-md font-body-md font-normal text-on-surface-variant">{{ __('ui.plans.per_month') }}</span>
                    </p>

                    <ul class="mt-6 space-y-2 text-label-md text-on-surface flex-1">
                        <li>· {{ $plan->allowsUnlimitedServices()
                            ? __('ui.plans.services_unlimited')
                            : trans_choice('ui.plans.services_limit', $plan->max_services, ['count' => $plan->max_services]) }}</li>
                        <li>· {{ $plan->allowsUnlimitedRequests()
                            ? __('ui.plans.requests_unlimited')
                            : trans_choice('ui.plans.requests_limit', $plan->max_monthly_requests, ['count' => $plan->max_monthly_requests]) }}</li>
                        @if ($plan->is_featured)
                            <li>· {{ __('ui.plans.featured') }}</li>
                        @endif
                        @if ($plan->has_ai_writing)
                            <li>· {{ __('ui.plans.ai_writing') }}</li>
                        @endif
                        @if ($plan->has_stats)
                            <li>· {{ __('ui.plans.stats') }}</li>
                        @endif
                    </ul>

                    @php
                        // Un abonnement en cours bloque la bascule vers le
                        // gratuit : `SubscriptionService` la refuse avec
                        // « still_running ». Le bouton le disait après le
                        // clic ; il le dit maintenant avant.
                        $gratuitDiffere = $plan->isFree() && ! $isCurrent && $subscription !== null;
                    @endphp

                    @if ($gratuitDiffere)
                        <p class="mt-6 rounded-full border border-outline-variant px-4 py-2.5 text-center text-label-md font-button-text font-semibold text-on-surface-variant">
                            {{ __('ui.subscription.free_later') }}
                        </p>
                        <p class="mt-2 text-label-sm text-on-surface-variant">
                            {{ __('ui.subscription.free_later_hint', ['days' => $subscription->daysRemaining()]) }}
                        </p>
                    @else
                        <a href="{{ route('provider.subscription.checkout', $plan) }}"
                           class="mt-6 block rounded-full bg-primary px-4 py-2.5 text-center text-label-md font-button-text font-semibold text-on-primary transition-colors hover:bg-primary-container">
                            {{-- « Renouveler » n'a pas de sens pour un palier gratuit :
                                 il se reconduit seul et rien n'est à régler. --}}
                            @if ($plan->isFree())
                                {{ $isCurrent ? __('ui.subscription.current_plan') : __('ui.subscription.free_confirm') }}
                            @elseif ($isCurrent && $subscription && ! $subscription->isTrial())
                                {{ __('ui.subscription.renew') }}
                            @else
                                {{ __('ui.subscription.choose_plan') }}
                            @endif
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
