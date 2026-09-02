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

<x-app-layout :titre="__('Tableau de bord')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Bonjour :prenom', ['prenom' => Str::of(auth()->user()->name)->trim()->explode(' ')->first()])"
                       :subtitle="__('Voici le résumé de votre activité.')">
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto flex max-w-container flex-col gap-8 px-margin-mobile py-gutter md:px-margin-tablet lg:px-margin-desktop">
        {{-- Le bandeau d'essai est permanent dans les maquettes, et non réservé
             aux sept derniers jours : c'est pendant l'essai qu'un prestataire
             décide s'il paiera. --}}
        @if ($subscription?->isTrial())
            <div class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border border-secondary-fixed-dim bg-secondary-fixed p-4 text-on-secondary-fixed md:flex-row md:items-center">
                <div class="flex items-center gap-3">
                    <x-icon name="info" />
                    <span class="font-body-md text-body-md font-semibold">
                        {{ __("Essai gratuit —") }} <span class="font-label-numeric">{{ $joursRestants }}</span> {{ Str::plural('jour', $joursRestants) }} {{ Str::plural('restant', $joursRestants) }}
                    </span>
                </div>
                <a href="{{ route('provider.subscription.show') }}"
                   class="self-end rounded-full bg-secondary px-6 py-2 font-button-text text-button-text text-on-secondary transition-all hover:opacity-90 active:scale-95 md:self-auto">
                    {{ __("Souscrire") }}
                </a>
            </div>
        @endif

        <x-stat-grid>
            <x-stat-tile variant="stacked" icon="mail" tone="secondary"
                         :value="$requestsThisMonth"
                         :label="__('Demandes reçues')"
                         hint="ce mois-ci"
                         :href="route('requests.index')" />
            <x-stat-tile variant="stacked" icon="pending_actions" tone="secondary"
                         :value="$pendingCount" :label="__('Demandes en attente')"
                         :hint="$remainingRequests === null ? 'Lecture sans limite' : $remainingRequests.' lisibles ce mois'"
                         :href="route('requests.index', ['status' => 'sent'])" />
            <x-stat-tile variant="stacked" icon="handyman" tone="secondary"
                         :value="$counts['in_progress'] ?? 0" :label="__('Prestations en cours')"
                         :href="route('requests.index', ['status' => 'in_progress'])" />
            <x-stat-tile variant="stacked" icon="star" tone="secondary"
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
                    <h2 class="font-headline-md text-headline-md text-on-background">{{ __("Demandes à traiter") }}</h2>
                    @if ($pendingRequests->isNotEmpty())
                        {{-- Le compteur des demandes à traiter est une attente, pas une
                             panne : il prend le jaune, comme les pastilles de la
                             liste qu'il annonce. --}}
                        <span class="rounded-full bg-secondary-container px-2 py-1 font-label-numeric text-label-numeric text-on-secondary-container">{{ $pendingCount }}</span>
                    @endif
                </div>

                @forelse ($pendingRequests as $demande)
                    @php $client = $demande->client; @endphp
                    <article class="flex flex-col items-start justify-between gap-4 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface p-4 md:flex-row md:items-center">
                        <a href="{{ route('requests.show', $demande) }}" class="group flex min-w-0 items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-outline-variant bg-surface-container font-semibold text-on-surface-variant">
                                {{ Str::upper(Str::substr($client?->clientProfile?->fullName() ?? $client?->name ?? '?', 0, 2)) }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-headline-sm text-headline-sm text-on-background group-hover:text-primary">
                                    {{ $client?->clientProfile?->fullName() ?? $client?->name }}
                                </h3>
                                <p class="font-body-md text-body-md text-on-surface-variant">
                                    {{ $demande->service?->category?->name }}@if ($demande->service) — {{ $demande->service->title }}@endif
                                </p>
                                <div class="mt-1 flex flex-wrap items-center gap-1 text-label-md text-on-surface-variant">
                                    @if ($demande->preferred_date)
                                        <x-icon name="schedule" size="xs" />
                                        <span class="font-label-numeric">{{ $demande->preferred_date->translatedFormat('j F') }}</span>
                                        <span class="mx-1 text-outline-variant" aria-hidden="true">•</span>
                                    @endif
                                    <span class="font-label-md text-label-md">{{ $demande->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </a>

                        <div class="mt-2 flex w-full gap-2 md:mt-0 md:w-auto">
                            <form action="{{ route('requests.refuse', $demande) }}" method="POST" class="flex-1 md:flex-none"
                                  onsubmit="return confirm('Refuser cette demande ?');">
                                @csrf
                                <button type="submit" class="w-full rounded-full border border-outline px-4 py-2 font-button-text text-button-text text-on-surface-variant transition-colors hover:bg-surface-container-low">
                                    {{ __("Refuser") }}
                                </button>
                            </form>
                            <form action="{{ route('requests.accept', $demande) }}" method="POST" class="flex-1 md:flex-none">
                                @csrf
                                <x-primary-button class="w-full">
                                    {{ __("Accepter") }}
                                </x-primary-button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-empty-state :title="__('Aucune demande en attente.')"
                                   :description="__('Les demandes qui vous parviennent s\'affichent ici, avec de quoi les accepter ou les refuser.')" />
                @endforelse
            </section>

            <section class="flex flex-col gap-4 md:col-span-4">
                <div class="flex items-center justify-between border-b border-outline-variant pb-2">
                    <h2 class="font-headline-md text-headline-md text-on-background">{{ __("Vos services") }}</h2>
                    @if ($masques > 0)
                        <span class="font-label-md text-label-md text-secondary">{{ trans_choice(":count masqué|:count masqués", $masques) }}</span>
                    @elseif ($auPlafond)
                        <span class="font-label-md text-label-md text-secondary">{{ __("Plafond atteint") }}</span>
                    @endif
                </div>

                @forelse ($services as $service)
                    <a href="{{ route('provider.services.edit', $service) }}"
                       class="group flex items-center justify-between rounded-lg border border-outline-variant bg-surface p-3 transition-colors hover:bg-surface-container-low">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="shrink-0 rounded-full bg-surface-container-high p-2 text-primary">
                                <x-category-icon :icon="$service->category?->icon" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate font-body-md text-body-md font-semibold text-on-background">{{ $service->title }}</h3>
                                <span class="font-label-numeric text-label-numeric text-primary">
                                    @if ($service->price_amount)
                                        À partir de {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                    @else
                                        Prix à convenir
                                    @endif
                                </span>
                            </div>
                        </div>
                        <x-icon name="chevron_right" class="shrink-0 text-outline transition-colors group-hover:text-primary" />
                    </a>
                @empty
                    <p class="text-label-md text-on-surface-variant">{{ __("Vous n'avez pas encore publié de service.") }}</p>
                @endforelse

                <a href="{{ route('provider.services.index') }}" class="mt-2 flex items-center gap-1 font-button-text text-button-text text-primary hover:underline">
                    {{ __("Gérer mes services") }}
                    <x-icon name="arrow_forward" />
                </a>
            </section>
        </div>
    </div>
</x-app-layout>
