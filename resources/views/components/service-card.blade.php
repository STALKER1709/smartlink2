@props(['service'])

@php
    $profile = $service->provider?->providerProfile;
@endphp

{{--
    Rangée de liste, pas carte.

    Ce composant était une carte blanche à coin arrondi, bordée d'un pixel,
    posée sur un fond gris, qui se soulevait au survol. Répété quinze fois
    dans une grille, ce motif ne hiérarchise rien : chaque service pèse
    exactement autant que le suivant, et l'ensemble a l'air d'une maquette.

    Un annuaire de services est une liste. Elle se parcourt de haut en bas,
    séparée par un filet, sans boîte autour de chaque ligne. C'est plus dense,
    plus rapide à lire, et plus rapide à charger — ce qui compte sur une 3G.
--}}
<a
    href="{{ route('services.show', $service) }}"
    class="group relative flex items-start gap-4 border-b border-outline-variant py-4 transition-colors duration-150 hover:bg-surface-container-low/70 sm:gap-5 sm:py-5"
>
    <div class="relative w-20 shrink-0 overflow-hidden rounded-lg bg-surface-container-high sm:w-28">
        <div class="aspect-square">
            <x-service-thumb :service="$service" />
        </div>

        @unless ($service->is_available)
            <span class="absolute inset-x-0 bottom-0 bg-inverse-surface/85 px-1 py-0.5 text-center font-label-sm text-label-sm font-semibold uppercase tracking-wide text-inverse-on-surface">
                {{ __("Indisponible") }}
            </span>
        @endunless
    </div>

    <div class="min-w-0 flex-1">
        {{-- Le métier avant le titre : c'est par lui qu'on cherche, et il
             situe l'annonce en un mot. La mise en avant est ce que paie un
             prestataire Pro : elle garde son badge entier, pas une abréviation.
             `PromotedBadgeTest` monte la garde. --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <p class="text-label-sm font-semibold uppercase tracking-wide text-primary">{{ $service->category?->name }}</p>
            <x-promoted-badge :profile="$profile" />
        </div>

        <h3 class="mt-1 font-headline-sm text-headline-sm leading-snug text-on-surface group-hover:text-primary">
            {{ $service->title }}
        </h3>

        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-label-md text-on-surface-variant">
            <span class="line-clamp-1">{{ $profile?->business_name ?? $service->provider?->name }}</span>
            @if ($profile?->is_verified)
                <x-icon name="verified" filled label="{{ __('Prestataire vérifié') }}" class="shrink-0 text-primary" />
            @endif
            @if ($service->city)
                <span class="whitespace-nowrap"><span aria-hidden="true" class="text-outline">·</span>
                    {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif</span>
            @endif
        </p>

        <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="font-label-numeric text-body-md text-on-surface">
                @if ($service->price_amount)
                    {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                    @if ($service->price_unit)
                        <span class="font-body-md text-label-md text-on-surface-variant">/ {{ $service->price_unit }}</span>
                    @endif
                @else
                    <span class="font-body-md text-label-md text-on-surface-variant">{{ __("Prix à convenir") }}</span>
                @endif
            </span>

            @if ($profile?->rating_count)
                <x-star-rating :rating="$profile->rating_avg" :count="$profile->rating_count" compact />
            @endif
        </div>
    </div>
</a>
