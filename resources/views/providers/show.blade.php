<x-app-layout>
    @if ($providerProfile->latitude && $providerProfile->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="h-20 w-20 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                            @if ($providerProfile->logo_path)
                                <img src="{{ asset('storage/'.$providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}" class="h-full w-full object-cover">
                            @else
                                <span class="text-2xl font-semibold text-gray-400">{{ Str::substr($providerProfile->business_name, 0, 1) }}</span>
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-bold text-gray-900">{{ $providerProfile->business_name }}</h1>
                                @if ($providerProfile->is_verified)
                                    <span class="text-indigo-600" title="Prestataire vérifié">
                                        <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20"><path d="M10 1l2.39 1.73 2.95-.1.91 2.8 2.39 1.74-1.13 2.83 1.13 2.83-2.39 1.74-.91 2.8-2.95-.1L10 19l-2.39-1.73-2.95.1-.91-2.8-2.39-1.74 1.13-2.83-1.13-2.83 2.39-1.74.91-2.8 2.95.1L10 1z"/></svg>
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">{{ $providerProfile->category?->name }} @if ($providerProfile->city) · {{ $providerProfile->city }} @endif</p>
                            <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" class="mt-1" />
                        </div>
                    </div>

                    @if ($providerProfile->description)
                        <p class="mt-4 text-gray-700 whitespace-pre-line">{{ $providerProfile->description }}</p>
                    @endif

                    @if ($providerProfile->address || $providerProfile->quarter)
                        <p class="mt-4 text-sm text-gray-600">
                            📍 {{ implode(', ', array_filter([$providerProfile->address, $providerProfile->quarter, $providerProfile->city])) }}
                        </p>
                    @endif

                    {{-- WhatsApp --}}
                    @if ($providerProfile->whatsappUrl())
                        <a href="{{ $providerProfile->whatsappUrl() }}" target="_blank" rel="noopener"
                           class="mt-3 inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                    @endif

                    {{-- Map --}}
                    @if ($providerProfile->latitude && $providerProfile->longitude)
                        <div class="mt-4">
                            <div id="provider-map-show" class="w-full h-48 rounded-md border border-gray-200 z-0"></div>
                        </div>
                    @endif

                    @if (! empty($providerProfile->service_areas))
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-900">Zones d'intervention</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($providerProfile->service_areas as $area)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-700">{{ $area }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($providerProfile->contact_methods))
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-900">Contact</h3>
                            <ul class="mt-2 text-sm text-gray-600 space-y-1">
                                @foreach ($providerProfile->contact_methods as $contact)
                                    <li>{{ $contact }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($providerProfile->opening_hours))
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-900">Horaires d'ouverture</h3>
                            <ul class="mt-2 text-sm text-gray-600 space-y-1">
                                @foreach ($providerProfile->opening_hours as $day => $hours)
                                    @if ($hours)
                                        <li class="flex justify-between max-w-xs">
                                            <span class="capitalize">{{ $day }}</span>
                                            <span>{{ $hours }}</span>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Services -->
                <div class="mt-8">
                    <h2 class="font-semibold text-gray-900 mb-4">Services proposés</h2>

                    @if ($services->isEmpty())
                        <p class="text-gray-500">Ce prestataire n'a pas encore publié de service.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($services as $service)
                                <x-service-card :service="$service" />
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $services->links() }}
                        </div>
                    @endif
                </div>

                <!-- Reviews -->
                <div class="mt-8">
                    <h2 class="font-semibold text-gray-900 mb-4">Avis clients</h2>

                    @if ($reviews->isEmpty())
                        <p class="text-gray-500">Aucun avis pour le moment.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($reviews as $review)
                                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-900">
                                            {{ $review->client?->clientProfile?->fullName() ?? $review->client?->name }}
                                        </span>
                                        <x-star-rating :rating="$review->rating" />
                                    </div>
                                    @if ($review->comment)
                                        <p class="mt-2 text-sm text-gray-700">{{ $review->comment }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    @auth
                        @can('create', \App\Models\ServiceRequest::class)
                            <a
                                href="{{ route('requests.create', ['provider_id' => $providerProfile->user_id]) }}"
                                class="block w-full text-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                Contacter ce prestataire
                            </a>
                        @else
                            <p class="text-sm text-gray-500 text-center">
                                Seuls les clients peuvent envoyer une demande.
                            </p>
                        @endcan
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="block w-full text-center rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Se connecter pour contacter
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    @if ($providerProfile->latitude && $providerProfile->longitude)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lat = {{ $providerProfile->latitude }};
                const lng = {{ $providerProfile->longitude }};
                const map = L.map('provider-map-show').setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                L.marker([lat, lng]).addTo(map)
                    .bindPopup('{{ addslashes($providerProfile->business_name) }}')
                    .openPopup();
            });
        </script>
    @endif
</x-app-layout>
