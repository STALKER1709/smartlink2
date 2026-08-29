@php
    $plan = $subscription?->plan;
    $joursRestants = $subscription?->daysRemaining() ?? 0;

    // « Services publiés » et « Services actifs » occupaient deux tuiles pour
    // dire la même chose : ils ne diffèrent que lorsqu'un service est masqué.
    $masques = max($servicesCount - $activeServicesCount, 0);
    $auPlafond = $plan !== null
        && ! $plan->allowsUnlimitedServices()
        && $servicesCount >= $plan->max_services;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('Bonjour :prenom', ['prenom' => Str::of(auth()->user()->name)->trim()->explode(' ')->first()])"
                       subtitle="Voici le résumé de votre activité.">
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto flex max-w-container flex-col gap-8 px-margin-mobile py-gutter md:px-margin-desktop">
        {{-- Le bandeau d'essai est permanent dans les maquettes, et non réservé
             aux sept derniers jours : c'est pendant l'essai qu'un prestataire
             décide s'il paiera. --}}
        @if ($subscription?->isTrial())
            <div class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border border-tertiary-fixed-dim bg-tertiary-fixed p-4 text-on-tertiary-fixed md:flex-row md:items-center">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined" aria-hidden="true">info</span>
                    <span class="font-body-md text-body-md font-semibold">
                        Essai gratuit — <span class="font-label-numeric">{{ $joursRestants }}</span> {{ Str::plural('jour', $joursRestants) }} {{ Str::plural('restant', $joursRestants) }}
                    </span>
                </div>
                <a href="{{ route('provider.subscription.show') }}"
                   class="self-end rounded-full bg-tertiary px-6 py-2 font-button-text text-button-text text-on-tertiary transition-all hover:opacity-90 active:scale-95 md:self-auto">
                    Souscrire
                </a>
            </div>
        @endif

        <x-stat-grid>
            <x-stat-tile variant="stacked" icon="mail" tone="secondary"
                         :value="$requestsThisMonth"
                         label="Demandes reçues"
                         hint="ce mois-ci"
                         :href="route('requests.index')" />
            <x-stat-tile variant="stacked" icon="pending_actions" tone="tertiary"
                         :value="$pendingCount" label="Demandes en attente"
                         :hint="$remainingRequests === null ? 'Lecture sans limite' : $remainingRequests.' lisibles ce mois'"
                         :href="route('requests.index', ['status' => 'sent'])" />
            <x-stat-tile variant="stacked" icon="handyman" tone="secondary"
                         :value="$counts['in_progress'] ?? 0" label="Prestations en cours"
                         :href="route('requests.index', ['status' => 'in_progress'])" />
            <x-stat-tile variant="stacked" icon="star" tone="tertiary"
                         :value="$profile?->rating_count ? number_format((float) $profile->rating_avg, 1, ',', ' ') : '—'"
                         label="Note moyenne"
                         :hint="$profile?->rating_count ? $profile->rating_count.' '.Str::plural('avis', $profile->rating_count) : 'Pas encore d\'avis'"
                         :href="route('provider.reviews.index')" />
        </x-stat-grid>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
            {{-- Arbitrer sans ouvrir la demande : c'est le geste que le
                 prestataire répète le plus, et il demandait deux navigations. --}}
            <section class="flex flex-col gap-4 md:col-span-8">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-headline-md text-headline-md text-on-background">Demandes à traiter</h3>
                    @if ($pendingRequests->isNotEmpty())
                        <span class="rounded-full bg-error-container px-2 py-1 font-label-numeric text-label-numeric text-on-error-container">{{ $pendingCount }}</span>
                    @endif
                </div>

                @forelse ($pendingRequests as $demande)
                    @php $client = $demande->client; @endphp
                    <article class="flex flex-col items-start justify-between gap-4 rounded-xl border border-outline-variant bg-surface p-4 md:flex-row md:items-center">
                        <a href="{{ route('requests.show', $demande) }}" class="group flex min-w-0 items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-outline-variant bg-surface-container font-semibold text-on-surface-variant">
                                {{ Str::upper(Str::substr($client?->clientProfile?->fullName() ?? $client?->name ?? '?', 0, 2)) }}
                            </span>
                            <div class="min-w-0">
                                <h4 class="font-headline-sm text-headline-sm text-on-background group-hover:text-primary">
                                    {{ $client?->clientProfile?->fullName() ?? $client?->name }}
                                </h4>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    {{ $demande->service?->category?->name }}@if ($demande->service) — {{ $demande->service->title }}@endif
                                </p>
                                <div class="mt-1 flex flex-wrap items-center gap-1 text-sm text-on-surface-variant">
                                    @if ($demande->preferred_date)
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">schedule</span>
                                        <span class="font-label-numeric">{{ $demande->preferred_date->translatedFormat('j F') }}</span>
                                        <span class="mx-1 text-outline-variant" aria-hidden="true">•</span>
                                    @endif
                                    <span class="font-label-numeric">{{ $demande->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>

                        <div class="mt-2 flex w-full gap-2 md:mt-0 md:w-auto">
                            <form action="{{ route('requests.refuse', $demande) }}" method="POST" class="flex-1 md:flex-none"
                                  onsubmit="return confirm('Refuser cette demande ?');">
                                @csrf
                                <button type="submit" class="w-full rounded-full border border-outline px-4 py-2 font-button-text text-button-text text-on-surface-variant transition-colors hover:bg-surface-container-low">
                                    Refuser
                                </button>
                            </form>
                            <form action="{{ route('requests.accept', $demande) }}" method="POST" class="flex-1 md:flex-none">
                                @csrf
                                <button type="submit" class="w-full rounded-full bg-primary px-6 py-2 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                                    Accepter
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="Aucune demande en attente."
                                   description="Les demandes qui vous parviennent s'affichent ici, avec de quoi les accepter ou les refuser." />
                @endforelse
            </section>

            <section class="flex flex-col gap-4 md:col-span-4">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-headline-md text-headline-md text-on-background">Vos services</h3>
                    @if ($masques > 0)
                        <span class="font-label-numeric text-label-numeric text-tertiary">{{ $masques }} masqué{{ $masques > 1 ? 's' : '' }}</span>
                    @elseif ($auPlafond)
                        <span class="font-label-numeric text-label-numeric text-tertiary">Plafond atteint</span>
                    @endif
                </div>

                @forelse ($services as $service)
                    <a href="{{ route('provider.services.edit', $service) }}"
                       class="group flex items-center justify-between rounded-lg border border-outline-variant bg-surface p-3 transition-colors hover:bg-surface-container-low">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="shrink-0 rounded-md bg-secondary-container p-2 text-on-secondary-container">
                                <x-category-icon :icon="$service->category?->icon" />
                            </span>
                            <div class="min-w-0">
                                <h4 class="truncate font-body-md text-body-md font-semibold text-on-background">{{ $service->title }}</h4>
                                <span class="font-label-numeric text-label-numeric text-primary">
                                    @if ($service->price_amount)
                                        À partir de {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                    @else
                                        Prix à convenir
                                    @endif
                                </span>
                            </div>
                        </div>
                        <span class="material-symbols-outlined shrink-0 text-outline transition-colors group-hover:text-primary" aria-hidden="true">chevron_right</span>
                    </a>
                @empty
                    <p class="text-sm text-on-surface-variant">Vous n'avez pas encore publié de service.</p>
                @endforelse

                <a href="{{ route('provider.services.index') }}" class="mt-2 flex items-center gap-1 font-button-text text-button-text text-primary hover:underline">
                    Gérer mes services
                    <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                </a>
            </section>
        </div>
    </div>
</x-app-layout>
