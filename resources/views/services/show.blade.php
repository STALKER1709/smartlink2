<x-app-layout>
    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <nav class="text-sm text-on-surface-variant mb-4">
            <a href="{{ route('services.index') }}" class="hover:text-primary">Services</a>
            <span class="mx-1">/</span>
            <span class="text-on-surface">{{ $service->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
                    @if ($service->images->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                            @foreach ($service->images as $image)
                                <img src="{{ media_url($image->path) }}" alt="{{ $service->title }}" class="h-40 w-full object-cover">
                            @endforeach
                        </div>
                    @else
                        <div class="h-56 bg-surface-container flex items-center justify-center text-on-surface-variant">
                            Aucune image
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-secondary">{{ $service->category?->name }}</span>
                            <x-status-badge :status="$service->is_available ? 'active' : 'inactive'" />
                        </div>

                        <h1 class="mt-2 font-headline-lg text-headline-lg text-on-surface">{{ $service->title }}</h1>

                        <p class="mt-1 text-sm text-on-surface-variant flex items-center gap-1">
                            @if ($service->city)
                                <span class="material-symbols-outlined text-base">location_on</span> {{ $service->city }}
                            @endif
                            @if ($service->location)
                                · {{ $service->location }}
                            @endif
                        </p>

                        <div class="mt-4 font-label-numeric text-xl text-on-surface">
                            @if ($service->price_amount)
                                {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                @if ($service->price_unit)
                                    <span class="text-sm text-on-surface-variant font-body-md">/ {{ $service->price_unit }}</span>
                                @endif
                            @else
                                <span class="text-base text-on-surface-variant font-body-md">Prix sur demande</span>
                            @endif
                        </div>

                        @if ($service->availability_note)
                            <p class="mt-2 text-sm text-tertiary bg-tertiary-container/15 border border-outline-variant rounded-lg px-3 py-2">
                                {{ $service->availability_note }}
                            </p>
                        @endif

                        <h2 class="mt-6 font-headline-md text-headline-md text-on-surface">Description</h2>
                        <p class="mt-2 text-on-surface whitespace-pre-line">{{ $service->description }}</p>
                    </div>
                </div>

                @if ($relatedServices->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-4">Services similaires</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($relatedServices as $related)
                                <x-service-card :service="$related" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                    <x-provider-card :provider-profile="$service->provider->providerProfile" />
                </div>

                <div class="mt-4 bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                    @auth
                        @can('create', \App\Models\ServiceRequest::class)
                            <a
                                href="{{ route('requests.create', ['service_id' => $service->id]) }}"
                                class="block w-full text-center rounded-full bg-primary px-4 py-2.5 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors"
                            >
                                Faire une demande
                            </a>
                        @else
                            <p class="text-sm text-on-surface-variant text-center">
                                Seuls les clients peuvent envoyer une demande.
                            </p>
                        @endcan
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="block w-full text-center rounded-full bg-primary px-4 py-2.5 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors"
                        >
                            Se connecter pour faire une demande
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
