<x-app-layout titre="Demande verrouillée" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.nav.requests')" />
    </x-slot>

    {{-- L'écran de plafond des maquettes : un seul bloc centré, la pastille,
         la phrase qui nomme le plafond, ce qui se passe, ce qui attend, et
         deux sorties. --}}
    <div class="mx-auto flex w-full max-w-[440px] px-margin-mobile py-12 md:px-margin-desktop">
        <article class="flex w-full flex-col items-center rounded-xl border border-outline-variant bg-surface p-6 text-center md:p-8">
            <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
                <span class="material-symbols-outlined text-[40px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">pause_circle</span>
            </div>

            <h1 class="mb-4 font-headline-md text-headline-md text-on-surface">
                {{ __('ui.subscription.quota_reached', ['count' => $plan?->max_monthly_requests ?? 0]) }}
            </h1>

            <p class="mb-6 px-2 font-body-md text-body-md text-on-surface-variant">
                {{ __('ui.subscription.quota_hidden') }} {{ __('ui.subscription.quota_upgrade') }}
            </p>

            <div class="mb-8 flex w-full items-start gap-3 rounded-lg border border-outline-variant bg-surface-container p-4">
                <span class="material-symbols-outlined mt-0.5 text-primary" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">info</span>
                <p class="flex-1 text-left font-label-numeric text-label-numeric text-on-surface-variant">
                    Cette demande vous attend : elle reste lisible dès que votre plafond se renouvelle, le premier jour du mois prochain.
                </p>
            </div>

            <div class="mt-auto flex w-full flex-col gap-4">
                <a href="{{ route('provider.subscription.show') }}"
                   class="flex w-full items-center justify-center rounded-full bg-primary px-8 py-[14px] font-button-text text-button-text text-on-primary transition-transform active:scale-[0.98]">
                    {{ __('ui.subscription.see_plans') }}
                </a>
                <a href="{{ route('requests.index') }}"
                   class="flex w-full items-center justify-center rounded-full border border-primary bg-surface px-8 py-[14px] font-button-text text-button-text text-primary transition-colors active:bg-surface-container-low">
                    {{ __('ui.subscription.back_to_requests') }}
                </a>
            </div>
        </article>
    </div>
</x-app-layout>
