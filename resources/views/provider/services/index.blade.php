@php
    $plan = auth()->user()->currentPlan();
    $plafond = $plan && ! $plan->allowsUnlimitedServices() ? $plan->max_services : null;
@endphp

<x-app-layout :titre="__('Mes services')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Mes services')">
            <x-slot name="subtitle">
                <span class="flex items-center gap-2 font-body-md text-body-md text-on-surface-variant">
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">inventory_2</span>
                    <span>
                        <span class="font-label-numeric">{{ $services->total() }}</span>
                        {{ trans_choice('service publié|services publiés', $services->total()) }}
                        @if ($plafond !== null)
                            {{ __('sur') }} <span class="font-label-numeric">{{ $plafond }}</span> {{ trans_choice('autorisé|autorisés', $plafond) }}
                        @endif
                    </span>
                </span>
            </x-slot>
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto w-full max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-outline-variant bg-secondary-container/30 px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($services->isEmpty())
            <x-empty-state
                :title="__('Vous n\'avez pas encore publié de service.')"
                :description="__('Un service bien décrit, avec une photo et un prix indicatif, est ce qui décide un client à vous écrire.')"
                :action-label="__('Publier mon premier service')"
                :action-href="route('provider.services.create')"
            />
        @else
            {{-- La carte de gestion des maquettes : grande vignette, état,
                 métier, titre, et le nombre de demandes reçues — le seul
                 chiffre qui dise si une annonce travaille. --}}
            <div class="flex flex-col gap-4 md:gap-6">
                @foreach ($services as $service)
                    @php $actif = $service->status === \App\Models\Service::STATUS_ACTIVE && $service->is_available; @endphp
                    <div class="group relative flex flex-col items-stretch overflow-hidden rounded-xl border border-outline-variant bg-surface p-4 transition-colors hover:border-primary-fixed-dim sm:flex-row sm:items-center">
                        <div class="relative mb-4 h-48 w-full shrink-0 overflow-hidden rounded-lg bg-surface-container-high sm:mb-0 sm:h-28 sm:w-28">
                            <x-service-thumb :service="$service" size="text-3xl" />
                            <span class="absolute left-3 top-3 flex items-center gap-1 rounded-full px-2 py-1 font-label-numeric text-[12px] shadow-sm sm:hidden {{ $actif ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface-variant' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $actif ? 'bg-primary' : 'bg-outline' }}"></span>
                                {{ $actif ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col justify-center sm:ml-6">
                            <div class="mb-2 hidden items-center gap-3 sm:flex">
                                <span class="flex items-center gap-1.5 rounded-full px-2.5 py-1 font-label-numeric text-[12px] {{ $actif ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface-variant' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $actif ? 'bg-primary' : 'bg-outline' }}"></span>
                                    {{ $actif ? 'Actif' : 'Inactif' }}
                                </span>
                                <span class="font-label-numeric text-[13px] uppercase tracking-wider text-on-surface-variant">{{ $service->category?->name }}</span>
                            </div>
                            <div class="mb-1 font-label-numeric text-[13px] uppercase tracking-wider text-on-surface-variant sm:hidden">{{ $service->category?->name }}</div>

                            <h3 class="mb-3 font-headline-md text-headline-md leading-tight text-on-surface">{{ $service->title }}</h3>

                            <div class="flex flex-wrap items-center gap-3 text-on-surface-variant">
                                <span class="flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5">
                                    <span class="material-symbols-outlined text-[18px] text-primary" aria-hidden="true">forum</span>
                                    <span class="font-label-numeric text-label-numeric font-bold text-on-surface">{{ $service->requests_count ?? 0 }}</span>
                                    <span class="font-body-md text-[14px]">{{ Str::plural('demande', $service->requests_count ?? 0) }}</span>
                                </span>
                                @if ($service->price_amount)
                                    <span class="font-label-numeric text-label-numeric text-primary">
                                        {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                        @if ($service->price_unit)<span class="text-on-surface-variant">/ {{ $service->price_unit }}</span>@endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 flex shrink-0 items-center justify-end gap-4 sm:ml-4 sm:mt-0">
                            <a href="{{ route('services.show', $service) }}" class="text-sm font-medium text-on-surface-variant hover:text-primary">{{ __("Voir") }}</a>
                            <a href="{{ route('provider.services.edit', $service) }}" class="text-sm font-medium text-primary hover:text-primary-container">{{ __("Modifier") }}</a>
                            <form action="{{ route('provider.services.destroy', $service) }}" method="POST"
                                  onsubmit="return confirm('Supprimer « {{ $service->title }} » ? Cette action est définitive.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-error hover:opacity-80">{{ __("Supprimer") }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
