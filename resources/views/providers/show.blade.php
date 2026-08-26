<x-app-layout>
    @php
        $aDesInfos = ! empty($providerProfile->service_areas)
            || ! empty($providerProfile->contact_methods)
            || ! empty($providerProfile->opening_hours);
        $adresse = implode(', ', array_filter([$providerProfile->address, $providerProfile->quarter, $providerProfile->city]));
    @endphp

    @if ($providerProfile->latitude && $providerProfile->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif

    {{-- Bandeau d'identité : nom, confiance et action, immédiatement visibles.
         Ils étaient auparavant noyés, l'action reléguée dans une colonne. --}}
    <section class="border-b border-outline-variant bg-surface-container-low">
        <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container-lowest">
                    @if ($providerProfile->logo_path)
                        <img src="{{ media_url($providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}" class="h-full w-full object-cover">
                    @else
                        <span class="font-headline-lg text-2xl font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-headline-lg text-headline-lg text-on-surface">{{ $providerProfile->business_name }}</h1>
                        @if ($providerProfile->is_verified)
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-container/20 px-2.5 py-1 text-xs font-semibold text-primary" title="Pièce d'identité contrôlée par notre équipe">
                                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">verified</span>
                                Vérifié
                            </span>
                        @endif
                        <x-promoted-badge :profile="$providerProfile" />
                    </div>

                    <p class="mt-1 text-sm text-on-surface-variant">
                        {{ $providerProfile->category?->name }}
                        @if ($providerProfile->city) · {{ $providerProfile->city }} @endif
                    </p>

                    <div class="mt-2 flex flex-wrap items-center gap-4">
                        @if ($providerProfile->rating_count)
                            <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" />
                        @else
                            <span class="text-sm text-on-surface-variant">Pas encore d'avis</span>
                        @endif
                        <span class="font-label-numeric text-label-numeric text-on-surface-variant">
                            {{ $services->total() }} {{ Str::plural('service', $services->total()) }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col gap-2 sm:w-56">
                    @include('partials.request-action', [
                        'href' => route('requests.create', ['provider_id' => $providerProfile->user_id]),
                        'label' => 'Contacter ce prestataire',
                    ])

                    @if ($providerProfile->whatsappUrl())
                        <a href="{{ $providerProfile->whatsappUrl() }}" target="_blank" rel="noopener"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-outline-variant bg-surface-container-lowest px-4 py-3 font-button-text font-semibold text-on-surface transition-colors hover:border-primary/50">
                            <svg class="h-4 w-4 fill-current text-[#25D366]" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-container px-margin-mobile py-8 pb-28 md:px-margin-desktop lg:pb-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                @if ($providerProfile->description)
                    <section class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
                        <h2 class="font-headline-md text-headline-md text-on-surface">À propos</h2>
                        <p class="prose-measure mt-3 whitespace-pre-line leading-relaxed text-on-surface">{{ $providerProfile->description }}</p>
                    </section>
                @endif

                <section>
                    <h2 class="font-headline-md text-headline-md text-on-surface">Services proposés</h2>

                    @if ($services->isEmpty())
                        <x-empty-state class="mt-4" title="Ce prestataire n'a pas encore publié de service." />
                    @else
                        <div class="mt-4 grid grid-cols-1 gap-4 xs:grid-cols-2 xs:gap-3 sm:gap-5">
                            @foreach ($services as $service)
                                <x-service-card :service="$service" />
                            @endforeach
                        </div>

                        <div class="mt-6">{{ $services->links() }}</div>
                    @endif
                </section>

                <section>
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="font-headline-md text-headline-md text-on-surface">Avis clients</h2>
                        @if ($providerProfile->rating_count)
                            <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" />
                        @endif
                    </div>

                    @if ($reviews->isEmpty())
                        <x-empty-state class="mt-4" icon="reviews" title="Aucun avis pour le moment."
                                       description="Les avis sont laissés par les clients après une prestation terminée." />
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($reviews as $review)
                                <article class="rounded-xl border border-outline-variant bg-surface-container-lowest p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-on-surface">
                                            {{ $review->client?->clientProfile?->fullName() ?? $review->client?->name }}
                                        </span>
                                        <x-star-rating :rating="$review->rating" />
                                    </div>
                                    @if ($review->comment)
                                        <p class="mt-2 leading-relaxed text-on-surface">{{ $review->comment }}</p>
                                    @endif
                                    <p class="mt-2 font-label-numeric text-xs text-on-surface-variant">{{ $review->created_at->format('d/m/Y') }}</p>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-4">
                @if ($adresse || ($providerProfile->latitude && $providerProfile->longitude))
                    <section class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest">
                        @if ($providerProfile->latitude && $providerProfile->longitude)
                            <div id="provider-map-show" class="z-0 h-48 w-full"></div>
                        @endif
                        @if ($adresse)
                            <p class="flex items-start gap-2 p-4 text-sm text-on-surface">
                                <span class="material-symbols-outlined text-base text-on-surface-variant">location_on</span>
                                {{ $adresse }}
                            </p>
                        @endif
                    </section>
                @endif

                @if ($aDesInfos)
                    <section class="space-y-5 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5">
                        @if (! empty($providerProfile->service_areas))
                            <div>
                                <h3 class="text-sm font-semibold text-on-surface">Zones d'intervention</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($providerProfile->service_areas as $area)
                                        <span class="rounded-full bg-surface-container px-2.5 py-1 text-xs text-on-surface-variant">{{ $area }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($providerProfile->opening_hours))
                            <div>
                                <h3 class="text-sm font-semibold text-on-surface">Horaires</h3>
                                <ul class="mt-2 space-y-1 text-sm text-on-surface-variant">
                                    @foreach ($providerProfile->opening_hours as $day => $hours)
                                        @if ($hours)
                                            <li class="flex justify-between gap-3">
                                                <span class="capitalize">{{ $day }}</span>
                                                <span class="font-label-numeric text-on-surface">{{ $hours }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($providerProfile->contact_methods))
                            <div>
                                <h3 class="text-sm font-semibold text-on-surface">Contact</h3>
                                <ul class="mt-2 space-y-1 text-sm text-on-surface-variant">
                                    @foreach ($providerProfile->contact_methods as $contact)
                                        <li>{{ $contact }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                @endif

                <p class="flex items-start gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-xs leading-relaxed text-on-surface-variant">
                    <span class="material-symbols-outlined text-base text-primary">shield</span>
                    SmartLink ne perçoit aucun paiement : le règlement se convient directement avec le prestataire.
                </p>
            </aside>
        </div>
    </div>

    <div class="action-bar fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="mx-auto max-w-container">
            @include('partials.request-action', [
                'href' => route('requests.create', ['provider_id' => $providerProfile->user_id]),
                'label' => 'Contacter ce prestataire',
                'compactLabel' => 'Contacter',
                'compact' => true,
            ])
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
                    .bindPopup(@js($providerProfile->business_name))
                    .openPopup();
            });
        </script>
    @endif
</x-app-layout>
