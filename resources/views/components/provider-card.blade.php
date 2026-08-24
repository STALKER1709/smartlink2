@props(['providerProfile'])

<a href="{{ route('providers.show', $providerProfile) }}" class="flex items-center gap-4 bg-surface-container-lowest rounded-lg border border-outline-variant p-4 hover:bg-surface-container-low transition-colors">
    <div class="h-16 w-16 rounded-full bg-surface-container flex items-center justify-center overflow-hidden shrink-0 border border-outline-variant">
        @if ($providerProfile->logo_path)
            <img src="{{ media_url($providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}" class="h-full w-full object-cover">
        @else
            <span class="text-lg font-semibold text-on-surface-variant">{{ Str::substr($providerProfile->business_name, 0, 1) }}</span>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <h3 class="font-headline-md text-base font-semibold text-on-surface truncate">{{ $providerProfile->business_name }}</h3>
            @if ($providerProfile->is_verified)
                <span class="text-primary shrink-0" title="Prestataire vérifié">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">verified</span>
                </span>
            @endif
        </div>
        <x-promoted-badge :profile="$providerProfile" class="mt-1" />
        <p class="text-sm text-on-surface-variant truncate">{{ $providerProfile->category?->name }} @if ($providerProfile->city) · {{ $providerProfile->city }} @endif</p>
        <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" class="mt-1" />
    </div>
</a>
