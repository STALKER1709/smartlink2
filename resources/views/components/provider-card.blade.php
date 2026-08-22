@props(['providerProfile'])

<a href="{{ route('providers.show', $providerProfile) }}" class="flex items-center gap-4 bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
    <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
        @if ($providerProfile->logo_path)
            <img src="{{ asset('storage/'.$providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}" class="h-full w-full object-cover">
        @else
            <span class="text-lg font-semibold text-gray-400">{{ Str::substr($providerProfile->business_name, 0, 1) }}</span>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <h3 class="font-semibold text-gray-900 truncate">{{ $providerProfile->business_name }}</h3>
            @if ($providerProfile->is_verified)
                <span class="text-brand-600" title="Prestataire vérifié">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M10 1l2.39 1.73 2.95-.1.91 2.8 2.39 1.74-1.13 2.83 1.13 2.83-2.39 1.74-.91 2.8-2.95-.1L10 19l-2.39-1.73-2.95.1-.91-2.8-2.39-1.74 1.13-2.83-1.13-2.83 2.39-1.74.91-2.8 2.95.1L10 1z"/></svg>
                </span>
            @endif
        </div>
        <x-promoted-badge :profile="$providerProfile" class="mt-1" />
        <p class="text-sm text-gray-500 truncate">{{ $providerProfile->category?->name }} @if ($providerProfile->city) · {{ $providerProfile->city }} @endif</p>
        <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" class="mt-1" />
    </div>
</a>
