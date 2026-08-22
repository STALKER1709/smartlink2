<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.nav.subscription') }}</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- État courant --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            @if ($subscription)
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.subscription.current_plan') }}</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $subscription->plan->name() }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $subscription->isTrial()
                                ? __('ui.subscription.trial_active', ['days' => $subscription->daysRemaining()])
                                : __('ui.subscription.expires_in', ['days' => $subscription->daysRemaining()]) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $subscription->isTrial() ? '0 FCFA' : $subscription->plan->formattedPrice() }}
                        </p>
                        <p class="text-sm text-gray-500">{{ __('ui.plans.per_month') }}</p>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-100 pt-6">
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('ui.subscription.services_used') }}</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $servicesUsed }}
                            @if (! $subscription->plan->allowsUnlimitedServices())
                                <span class="text-gray-400">/ {{ $subscription->plan->max_services }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">{{ __('ui.subscription.requests_used') }}</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ $requestsRead }}
                            @if (! $subscription->plan->allowsUnlimitedRequests())
                                <span class="text-gray-400">/ {{ $subscription->plan->max_monthly_requests }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            @else
                <p class="text-lg font-semibold text-red-800">{{ __('ui.subscription.expired') }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ __('ui.subscription.expired_hint') }}</p>
            @endif
        </div>

        {{-- Paliers --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($plans as $plan)
                @php $isCurrent = $subscription && $subscription->plan_id === $plan->id; @endphp
                <div @class([
                    'bg-white rounded-lg border p-6 flex flex-col',
                    'border-indigo-500 ring-1 ring-indigo-500' => $isCurrent,
                    'border-gray-200' => ! $isCurrent,
                ])>
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name() }}</h3>
                        @if ($isCurrent)
                            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                {{ __('ui.subscription.current_plan') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $plan->tagline() }}</p>

                    <p class="mt-4 text-3xl font-semibold text-gray-900">
                        {{ $plan->formattedPrice() }}
                        <span class="text-base font-normal text-gray-500">{{ __('ui.plans.per_month') }}</span>
                    </p>

                    <ul class="mt-6 space-y-2 text-sm text-gray-700 flex-1">
                        <li>· {{ $plan->allowsUnlimitedServices()
                            ? __('ui.plans.services_unlimited')
                            : __('ui.plans.services_limit', ['count' => $plan->max_services]) }}</li>
                        <li>· {{ $plan->allowsUnlimitedRequests()
                            ? __('ui.plans.requests_unlimited')
                            : __('ui.plans.requests_limit', ['count' => $plan->max_monthly_requests]) }}</li>
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

                    <a href="{{ route('provider.subscription.checkout', $plan) }}"
                       class="mt-6 block rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700">
                        {{ $isCurrent && $subscription && ! $subscription->isTrial()
                            ? __('ui.subscription.renew')
                            : __('ui.subscription.choose_plan') }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
