@props(['service'])

<a href="{{ route('services.show', $service) }}" class="flex flex-col bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
    <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
        @if ($service->images->isNotEmpty())
            <img src="{{ asset('storage/'.$service->images->first()->path) }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
        @else
            <span class="text-gray-400 text-sm">Aucune image</span>
        @endif
    </div>

    <div class="flex-1 flex flex-col p-4">
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-brand-600">{{ $service->category?->name }}</span>
            @if (! $service->is_available)
                <x-status-badge status="inactive" class="!bg-amber-100 !text-amber-700" />
            @endif
        </div>

        <h3 class="mt-1 font-semibold text-gray-900 line-clamp-2">{{ $service->title }}</h3>

        <p class="mt-1 text-sm text-gray-500">
            {{ $service->provider?->providerProfile?->business_name ?? $service->provider?->name }}
            <x-promoted-badge :profile="$service->provider?->providerProfile" class="ml-1 align-middle" />
            @if ($service->city)
                · {{ $service->city }}
            @endif
        </p>

        <div class="mt-auto pt-3 text-sm font-medium text-gray-900">
            @if ($service->price_amount)
                {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                @if ($service->price_unit)
                    <span class="text-gray-500 font-normal">/ {{ $service->price_unit }}</span>
                @endif
            @else
                <span class="text-gray-500 font-normal">Prix sur demande</span>
            @endif
        </div>
    </div>
</a>
