<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('services.index') }}" class="hover:text-brand-600">Services</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700">{{ $service->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    @if ($service->images->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                            @foreach ($service->images as $image)
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $service->title }}" class="h-40 w-full object-cover">
                            @endforeach
                        </div>
                    @else
                        <div class="h-56 bg-gray-100 flex items-center justify-center text-gray-400">
                            Aucune image
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-brand-600">{{ $service->category?->name }}</span>
                            <x-status-badge :status="$service->is_available ? 'active' : 'inactive'" />
                        </div>

                        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $service->title }}</h1>

                        <p class="mt-1 text-sm text-gray-500">
                            @if ($service->city)
                                📍 {{ $service->city }}
                            @endif
                            @if ($service->location)
                                · {{ $service->location }}
                            @endif
                        </p>

                        <div class="mt-4 text-xl font-semibold text-gray-900">
                            @if ($service->price_amount)
                                {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                @if ($service->price_unit)
                                    <span class="text-sm text-gray-500 font-normal">/ {{ $service->price_unit }}</span>
                                @endif
                            @else
                                <span class="text-base text-gray-500 font-normal">Prix sur demande</span>
                            @endif
                        </div>

                        @if ($service->availability_note)
                            <p class="mt-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
                                {{ $service->availability_note }}
                            </p>
                        @endif

                        <h2 class="mt-6 font-semibold text-gray-900">Description</h2>
                        <p class="mt-2 text-gray-700 whitespace-pre-line">{{ $service->description }}</p>
                    </div>
                </div>

                @if ($relatedServices->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="font-semibold text-gray-900 mb-4">Services similaires</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($relatedServices as $related)
                                <x-service-card :service="$related" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <x-provider-card :provider-profile="$service->provider->providerProfile" />
                </div>

                <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    @auth
                        @can('create', \App\Models\ServiceRequest::class)
                            <a
                                href="{{ route('requests.create', ['service_id' => $service->id]) }}"
                                class="block w-full text-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                            >
                                Faire une demande
                            </a>
                        @else
                            <p class="text-sm text-gray-500 text-center">
                                Seuls les clients peuvent envoyer une demande.
                            </p>
                        @endcan
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="block w-full text-center rounded-md bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
                        >
                            Se connecter pour faire une demande
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
