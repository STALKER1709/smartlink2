<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.nav.requests') }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-12">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-8 text-center">
            <span class="material-symbols-outlined text-5xl text-tertiary mb-3">lock</span>

            <h3 class="font-headline-md text-headline-md text-on-surface">
                {{ __('ui.subscription.quota_reached', ['count' => $plan?->max_monthly_requests ?? 0]) }}
            </h3>

            <p class="mt-3 text-sm text-on-surface-variant">{{ __('ui.subscription.quota_hidden') }}</p>
            <p class="mt-2 text-sm text-on-surface-variant">{{ __('ui.subscription.quota_upgrade') }}</p>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('provider.subscription.show') }}" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    {{ __('ui.subscription.see_plans') }}
                </a>
                <a href="{{ route('requests.index') }}" class="rounded-full border border-primary px-4 py-2 text-sm font-button-text font-semibold text-primary hover:bg-primary-container/10 transition-colors">
                    {{ __('ui.subscription.back_to_requests') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
