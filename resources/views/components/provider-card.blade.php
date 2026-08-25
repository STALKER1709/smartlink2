@props(['providerProfile'])

<a
    href="{{ route('providers.show', $providerProfile) }}"
    class="group flex flex-col gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 transition-all hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
>
    <div class="flex items-start gap-4">
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-secondary-container/40">
            @if ($providerProfile->logo_path)
                <img src="{{ media_url($providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="font-headline-md text-lg font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h3 class="flex items-center gap-1.5 font-headline-md text-base font-semibold text-on-surface group-hover:text-primary">
                <span class="truncate">{{ $providerProfile->business_name }}</span>
                @if ($providerProfile->is_verified)
                    <span class="material-symbols-outlined shrink-0 text-lg text-primary" style="font-variation-settings: 'FILL' 1;" title="Prestataire vérifié">verified</span>
                @endif
            </h3>

            <p class="mt-0.5 truncate text-sm text-on-surface-variant">
                {{ $providerProfile->category?->name }}
                @if ($providerProfile->city)
                    · {{ $providerProfile->city }}
                @endif
            </p>

            @if ($providerProfile->rating_count)
                <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" class="mt-1.5" />
            @else
                <p class="mt-1.5 text-xs text-on-surface-variant">Pas encore d'avis</p>
            @endif
        </div>
    </div>

    @if ($providerProfile->description)
        <p class="text-sm leading-relaxed text-on-surface-variant line-clamp-2">{{ $providerProfile->description }}</p>
    @endif

    <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-outline-variant pt-3">
        <x-promoted-badge :profile="$providerProfile" />

        @isset($providerProfile->services_count)
            <span class="rounded-full bg-surface-container px-2.5 py-1 text-xs font-medium text-on-surface-variant">
                {{ $providerProfile->services_count }} {{ Str::plural('service', $providerProfile->services_count) }}
            </span>
        @endisset

        <span class="ml-auto flex items-center gap-1 text-sm font-semibold text-primary">
            Voir le profil
            <span class="material-symbols-outlined text-base transition-transform group-hover:translate-x-0.5">arrow_forward</span>
        </span>
    </div>
</a>
