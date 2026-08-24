@props(['service'])

<a href="{{ route('services.show', $service) }}" class="flex flex-col bg-surface-container-lowest rounded-lg border border-outline-variant overflow-hidden hover:bg-surface-container-low transition-colors">
    <div class="h-40 bg-surface-container flex items-center justify-center overflow-hidden">
        @if ($service->images->isNotEmpty())
            <img src="{{ media_url($service->images->first()->path) }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
        @else
            <span class="text-on-surface-variant text-sm">Aucune image</span>
        @endif
    </div>

    <div class="flex-1 flex flex-col p-4">
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-semibold text-secondary">{{ $service->category?->name }}</span>
            @if (! $service->is_available)
                <x-status-badge status="inactive" />
            @endif
        </div>

        <h3 class="mt-1 font-headline-md text-base font-semibold text-on-surface line-clamp-2">{{ $service->title }}</h3>

        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $service->provider?->providerProfile?->business_name ?? $service->provider?->name }}
            <x-promoted-badge :profile="$service->provider?->providerProfile" class="ml-1 align-middle" />
            @if ($service->city)
                · {{ $service->city }}
            @endif
        </p>

        <div class="mt-auto pt-3 font-label-numeric text-label-numeric text-on-surface">
            @if ($service->price_amount)
                {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                @if ($service->price_unit)
                    <span class="text-on-surface-variant font-body-md">/ {{ $service->price_unit }}</span>
                @endif
            @else
                <span class="text-on-surface-variant font-body-md">Prix sur demande</span>
            @endif
        </div>
    </div>
</a>
