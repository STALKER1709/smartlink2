<x-app-layout>
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

                    @if ($providerProfile->address)
                        <p class="mt-4 text-sm text-gray-600">📍 {{ $providerProfile->address }}</p>
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
</x-app-layout>
