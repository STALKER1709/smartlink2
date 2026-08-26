@props(['service'])

@php
    $profile = $service->provider?->providerProfile;
@endphp

<a
    href="{{ route('services.show', $service) }}"
    {{-- Horizontale en dessous de 480 px, verticale au-delà. Sur un
         téléphone, la carte verticale pleine largeur consacre près de trois
         cents pixels à l'illustration : deux cartes par écran, là où la forme
         horizontale en montre cinq sans rien couper. --}}
    class="group flex flex-row xs:flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
>
    <div class="relative aspect-square w-32 shrink-0 self-stretch overflow-hidden bg-secondary-container/40 xs:aspect-[4/3] xs:w-auto">
        @if ($service->images->isNotEmpty())
            <img
                src="{{ media_url($service->images->first()->path) }}"
                alt="{{ $service->title }}"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            >
        @else
            {{-- La plupart des prestataires ne déposent pas de photo : l'icône de
                 la catégorie vaut mieux qu'un rectangle gris annonçant un manque. --}}
            <div class="flex h-full w-full items-center justify-center">
                <x-category-icon :icon="$service->category?->icon" class="text-5xl text-primary/35" />
            </div>
        @endif

        @if ($service->category)
            <span class="absolute left-3 top-3 hidden rounded-full bg-surface-container-lowest/95 px-2.5 py-1 text-xs font-semibold text-primary shadow-sm backdrop-blur xs:inline-block">
                {{ $service->category->name }}
            </span>
        @endif

        @if (! $service->is_available)
            <span class="absolute right-3 top-3">
                <x-status-badge status="inactive" />
            </span>
        @endif

        @if ($profile?->is_promoted)
            <span class="absolute bottom-3 left-3 hidden xs:inline-block">
                <x-promoted-badge :profile="$profile" class="bg-surface-container-lowest/95 shadow-sm backdrop-blur" />
            </span>
        @endif
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-1.5 p-3 sm:gap-2 sm:p-4">
        {{-- En format horizontal, la catégorie et la mise en avant n'ont pas
             la place de tenir sur la vignette : elles passent ici. --}}
        <div class="flex flex-wrap items-center gap-1.5 xs:hidden">
            @if ($service->category)
                <span class="rounded-full bg-secondary-container/40 px-2 py-0.5 text-xs font-semibold text-primary">{{ $service->category->name }}</span>
            @endif
            @if ($profile?->is_promoted)
                <x-promoted-badge :profile="$profile" />
            @endif
        </div>

        <h3 class="font-headline-md text-sm font-semibold leading-snug text-on-surface line-clamp-2 group-hover:text-primary sm:text-base">
            {{ $service->title }}
        </h3>

        <p class="flex items-center gap-1 text-xs text-on-surface-variant sm:text-sm">
            <span class="truncate">{{ $profile?->business_name ?? $service->provider?->name }}</span>
            @if ($profile?->is_verified)
                <span class="material-symbols-outlined shrink-0 text-base text-primary" style="font-variation-settings: 'FILL' 1;" title="Prestataire vérifié">verified</span>
            @endif
        </p>

        @if ($profile?->rating_count)
            <x-star-rating :rating="$profile->rating_avg" :count="$profile->rating_count" compact />
        @endif

        @if ($service->city)
            <p class="flex items-center gap-1 text-xs text-on-surface-variant sm:text-sm">
                <span class="material-symbols-outlined text-base">location_on</span>
                {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif
            </p>
        @endif

        <div class="mt-auto border-outline-variant pt-1.5 xs:border-t xs:pt-2.5 sm:pt-3">
            <div class="font-label-numeric text-sm text-on-surface sm:text-label-numeric">
                @if ($service->price_amount)
                    {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                    @if ($service->price_unit)
                        <span class="font-body-md text-on-surface-variant">/ {{ $service->price_unit }}</span>
                    @endif
                @else
                    <span class="font-body-md text-on-surface-variant">Prix à convenir</span>
                @endif
            </div>
        </div>
    </div>
</a>
