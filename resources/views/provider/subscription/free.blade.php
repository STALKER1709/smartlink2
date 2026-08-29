<x-app-layout :titre="__('Mon abonnement')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.subscription.free_title', ['plan' => $plan->name()])" :back="route('provider.subscription.show')" back-label="Mon abonnement" />
    </x-slot>

    <div class="max-w-xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">

            <div class="flex items-baseline justify-between border-b border-outline-variant pb-4">
                <div>
                    <p class="font-semibold text-on-surface">{{ $plan->name() }}</p>
                    <p class="text-label-lg text-on-surface-variant">{{ $plan->tagline() }}</p>
                </div>
                <p class="font-label-numeric text-headline-md text-on-surface">{{ $plan->formattedPrice() }}</p>
            </div>

            <x-input-error :messages="$errors->get('plan')" class="mt-4" />

            {{-- Ce que la formule ouvre, et surtout ce qu'elle ferme : un
                 prestataire qui découvre ses limites après coup se croit
                 victime d'une panne. --}}
            <p class="mt-6 text-label-lg font-semibold text-on-surface">{{ __('ui.subscription.free_included') }}</p>
            <ul class="mt-2 space-y-1.5 text-label-lg text-on-surface">
                <li class="flex gap-2">
                    <span class="material-symbols-outlined text-base text-primary">check</span>
                    <span>{{ trans_choice('ui.plans.services_limit', $plan->max_services, ['count' => $plan->max_services]) }}</span>
                </li>
                <li class="flex gap-2">
                    <span class="material-symbols-outlined text-base text-primary">check</span>
                    <span>{{ trans_choice('ui.plans.requests_limit', $plan->max_monthly_requests, ['count' => $plan->max_monthly_requests]) }}</span>
                </li>
            </ul>

            <p class="mt-6 text-label-lg font-semibold text-on-surface">{{ __('ui.subscription.free_excluded') }}</p>
            <ul class="mt-2 space-y-1.5 text-label-lg text-on-surface-variant">
                @foreach (['ui.plans.featured', 'ui.plans.ai_writing', 'ui.plans.stats'] as $manque)
                    <li class="flex gap-2">
                        <span class="material-symbols-outlined text-base text-outline">close</span>
                        <span>{{ __($manque) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 rounded-lg bg-secondary-container/20 border border-outline-variant p-4 text-label-lg text-on-secondary-container space-y-2">
                <p>{{ __('ui.subscription.free_cap_warning', ['count' => $plan->max_services]) }}</p>
                <p>{{ __('ui.subscription.free_upgrade_hint') }}</p>
            </div>

            <form action="{{ route('provider.subscription.free', $plan) }}" method="POST" class="mt-6">
                @csrf

                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('provider.subscription.show') }}" class="text-label-lg text-on-surface-variant hover:text-on-surface">
                        {{ __('ui.cancel') }}
                    </a>
                    <x-primary-button>
                        {{ __('ui.subscription.free_confirm') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
