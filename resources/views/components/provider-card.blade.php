@props(['providerProfile'])

{{--
    Une rangée de prestataire — une liste, pas une carte.

    C'était une boîte blanche à coin arrondi, bordée d'un pixel, répétée cinq
    fois sur l'accueil et vingt fois dans l'annuaire, à cinq cents pixels
    l'une : la page s'ouvrait sur deux mille pixels de prestataires avant le
    premier service. Et elle se soulevait au survol, avec une ombre, dans une
    page où les services voisins, eux, ne bougent pas — deux motifs pour le
    même travail sur le même écran.

    Le métier passe devant le nom, comme sur la rangée de service : c'est par
    lui que l'on cherche.
--}}
<a
    href="{{ route('providers.show', $providerProfile) }}"
    {{ $attributes->merge(['class' => 'group flex items-start gap-4 border-b border-outline-variant py-4 transition-colors duration-150 hover:bg-surface-container-low/70 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:gap-5 sm:py-5']) }}
>
    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-secondary-container/40 sm:h-16 sm:w-16">
        @if ($providerProfile->logo_path)
            <img src="{{ media_url($providerProfile->logo_path) }}" alt="" loading="lazy"
                 class="h-full w-full object-cover" onerror="this.remove()">
        @else
            <span class="font-headline-md text-lg font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $providerProfile->category?->name }}</p>
            <x-promoted-badge :profile="$providerProfile" />
        </div>

        {{-- Le nom passe à la ligne plutôt que de se couper : « Tchoumi
             Électricité Bâtim… » ne désigne personne. --}}
        <h3 class="mt-1 flex items-start gap-1.5 font-headline-sm text-headline-sm leading-snug text-on-surface group-hover:text-primary">
            <span class="line-clamp-2">{{ $providerProfile->business_name }}</span>
            @if ($providerProfile->is_verified)
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-primary" style="font-variation-settings: 'FILL' 1;" role="img" aria-label="{{ __('Prestataire vérifié') }}">verified</span>
            @endif
        </h3>

        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $providerProfile->city }}@if ($providerProfile->quarter), {{ $providerProfile->quarter }}@endif
            @isset($providerProfile->services_count)
                <span aria-hidden="true" class="text-outline">·</span>
                <span class="font-label-numeric">{{ $providerProfile->services_count }}</span>
                {{ Str::plural('service', $providerProfile->services_count) }}
            @endisset
        </p>

        <div class="mt-2">
            @if ($providerProfile->rating_count)
                <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" compact />
            @else
                <span class="text-xs text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
            @endif
        </div>
    </div>
</a>
