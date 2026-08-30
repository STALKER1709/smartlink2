@php
    /*
     * L'autre page qu'on partage. Le métier et la ville portent la recherche
     * locale ; la note, la confiance.
     */
    $metier = $providerProfile->category?->name ?? __('Prestataire de services');

    $titreSeo = $providerProfile->city
        ? __('seo.provider', [
            'nom' => $providerProfile->business_name,
            'metier' => $metier,
            'ville' => $providerProfile->city,
        ])
        : __('seo.provider_no_city', [
            'nom' => $providerProfile->business_name,
            'metier' => $metier,
        ]);

    $reputation = $providerProfile->rating_count > 0
        ? __(':note/5 sur :nombre', [
            'note' => number_format((float) $providerProfile->rating_avg, 1, ',', ' '),
            'nombre' => trans_choice(':count avis|:count avis', $providerProfile->rating_count),
        ])
        : null;

    $descriptionSeo = implode(' · ', array_filter([
        $providerProfile->is_verified ? __('Prestataire vérifié') : null,
        $reputation,
        Str::limit($providerProfile->description, 110),
    ])) ?: __('seo.default_description');

    $imageSeo = $providerProfile->logo_path ? media_url($providerProfile->logo_path) : null;
@endphp

<x-app-layout :titre="$titreSeo" :description="$descriptionSeo" :image-partage="$imageSeo">
    @php
        $adresse = implode(', ', array_filter([$providerProfile->address, $providerProfile->quarter, $providerProfile->city]));
        $aUneCarte = $providerProfile->latitude && $providerProfile->longitude;

        $aDesInfos = ! empty($providerProfile->service_areas)
            || ! empty($providerProfile->contact_methods)
            || ! empty($providerProfile->opening_hours)
            || $adresse !== '';
    @endphp

    @if ($aUneCarte)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif

    <div class="mx-auto max-w-container space-y-8 px-margin-mobile py-8 pb-28 md:px-margin-tablet lg:px-margin-desktop lg:pb-8">
        {{--
            L'identité au centre, comme la maquette : portrait, nom, métier,
            note, gages de confiance, puis les deux actions. La fiche était
            auparavant un bandeau à trois colonnes où l'action se rangeait à
            droite — sur un téléphone elle tombait sous le pli, et c'est
            pourtant la seule chose que le visiteur est venu faire.
        --}}
        <section class="relative flex flex-col items-center gap-4 text-center">
            <x-favorite-button :provider-profile="$providerProfile" contour class="absolute right-0 top-0" />

            <div class="relative">
                <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-surface-container-lowest bg-surface-container shadow-elevation-1 sm:h-32 sm:w-32">
                    @if ($providerProfile->logo_path)
                        <img src="{{ media_url($providerProfile->logo_path) }}" alt="{{ $providerProfile->business_name }}"
                             class="h-full w-full object-cover" onerror="this.remove()">
                    @else
                        <span class="font-headline-lg text-display-lg font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
                    @endif
                </div>

                @if ($providerProfile->is_verified)
                    <span class="absolute -bottom-1 -right-1 flex items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest p-1"
                          title="{{ __('Pièce d\'identité contrôlée par notre équipe') }}">
                        <x-icon name="verified" size="xl" filled label="{{ __('Prestataire vérifié') }}" class="text-primary" />
                    </span>
                @endif
            </div>

            <div class="space-y-2">
                <h1 class="font-display-lg text-headline-lg sm:text-display-lg text-on-surface">{{ $providerProfile->business_name }}</h1>

                <p class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-on-surface-variant">
                    @if ($providerProfile->category)
                        <span class="inline-flex items-center gap-1.5">
                            <x-category-icon :icon="$providerProfile->category->icon" class="text-lg" aria-hidden="true" />
                            {{ $providerProfile->category->name }}
                        </span>
                    @endif

                    @if ($providerProfile->category && $providerProfile->city)
                        <span aria-hidden="true" class="text-outline">•</span>
                    @endif

                    @if ($providerProfile->city)
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="location_on" size="lg" />
                            {{ $providerProfile->city }}
                        </span>
                    @endif
                </p>

                <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
                    @if ($providerProfile->rating_count)
                        <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" compact />
                    @else
                        <span class="text-label-md text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
                    @endif
                    <span aria-hidden="true" class="text-outline">·</span>
                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">
                        {{ $services->total() }} {{ Str::plural('service', $services->total()) }}
                    </span>
                </div>
            </div>

            @if ($providerProfile->is_verified || $providerProfile->is_promoted)
                <div class="flex flex-wrap justify-center gap-2">
                    @if ($providerProfile->is_verified)
                        <span class="inline-flex items-center gap-1 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-label-md font-semibold text-primary">
                            <x-icon name="workspace_premium" size="sm" />
                            {{ __("Prestataire vérifié") }}
                        </span>
                    @endif
                    <x-promoted-badge :profile="$providerProfile" taille="md" />
                </div>
            @endif

            {{-- Les deux actions à égalité de largeur, l'une pleine, l'autre
                 bordée : la demande passe par la plateforme et laisse une
                 trace, WhatsApp est direct. --}}
            {{-- `items-start` : pour un visiteur non connecté, le bouton de
                 demande porte deux lignes de mention sous lui. Sans cette
                 règle, la rangée étirait le bouton WhatsApp à la hauteur de
                 la colonne voisine — une pastille de 75 px à côté d'une de
                 48 px. --}}
            <div class="flex w-full flex-col gap-3 pt-2 sm:flex-row sm:items-start sm:justify-center">
                <div class="sm:w-72">
                    @include('partials.request-action', [
                        'href' => route('requests.create', ['provider_id' => $providerProfile->user_id]),
                        'label' => __('Envoyer une demande'),
                        'icon' => 'mail',
                    ])
                </div>

                @if ($providerProfile->whatsappUrl())
                    <a href="{{ $providerProfile->whatsappUrl() }}" target="_blank" rel="noopener"
                       class="inline-flex items-center justify-center gap-2 rounded-full border border-primary bg-surface-container-lowest px-4 py-3 font-button-text font-semibold text-primary transition-colors hover:bg-surface-container-low sm:w-72">
                        <svg class="h-4 w-4 fill-current text-[#25D366]" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        {{ __("WhatsApp") }}
                    </a>
                @endif
            </div>

            <p class="flex items-start gap-2 text-left text-label-sm leading-relaxed text-on-surface-variant sm:text-center">
                <x-icon name="shield" class="text-primary" />
                {{ __("SmartLink ne perçoit aucun paiement : le règlement se convient directement avec le prestataire.") }}
            </p>
        </section>

        {{-- Tout ce qui décrit le prestataire tient dans une seule carte :
             présentation, horaires, zones, contact, puis la carte
             géographique. C'était auparavant une colonne latérale, invisible
             sur téléphone tant qu'on n'avait pas dépassé toute la liste des
             services. --}}
        @if ($providerProfile->description || $aDesInfos || $aUneCarte)
            <section class="space-y-6 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-5 sm:p-6">
                @if ($providerProfile->description)
                    <div>
                        <h2 class="mb-3 flex items-center gap-2 font-headline-md text-headline-md text-on-surface">
                            <x-icon name="info" class="text-primary" />
                            {{ __("À propos") }}
                        </h2>
                        <p class="prose-measure whitespace-pre-line leading-relaxed text-on-surface-variant">{{ $providerProfile->description }}</p>
                    </div>
                @endif

                @if ($aDesInfos)
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        @if (! empty($providerProfile->opening_hours))
                            <div>
                                <h3 class="mb-2 flex items-center gap-2 font-button-text font-semibold text-on-surface">
                                    <x-icon name="schedule" class="text-on-surface-variant" />
                                    {{ __("Horaires") }}
                                </h3>
                                <ul class="space-y-1 text-on-surface-variant">
                                    {{-- La clé est celle de la base et pouvait
                                         atteindre l'écran telle quelle :
                                         « Lundi_vendredi ». Elle est écrite
                                         pour être lue, quelle que soit sa
                                         forme — un jour ou une plage. --}}
                                    @foreach ($providerProfile->opening_hours as $day => $hours)
                                        @if ($hours)
                                            <li class="flex justify-between gap-3 border-b border-outline-variant/30 pb-1">
                                                <span>{{ Str::of($day)->replace('_', ' – ')->ucfirst() }}</span>
                                                <span class="font-label-numeric text-on-surface">{{ $hours }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (! empty($providerProfile->service_areas))
                            <div>
                                <h3 class="mb-2 flex items-center gap-2 font-button-text font-semibold text-on-surface">
                                    <x-icon name="map" class="text-on-surface-variant" />
                                    {{ __("Zones d'intervention") }}
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($providerProfile->service_areas as $area)
                                        <span class="rounded-full bg-surface-container px-3 py-1 text-label-md text-on-surface-variant">{{ $area }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($providerProfile->contact_methods))
                            <div>
                                <h3 class="mb-2 flex items-center gap-2 font-button-text font-semibold text-on-surface">
                                    <x-icon name="call" class="text-on-surface-variant" />
                                    {{ __("Contact") }}
                                </h3>
                                <ul class="space-y-1 text-on-surface-variant">
                                    {{-- Deux formes coexistent en base : une
                                         liste de valeurs, écrite par le
                                         formulaire de profil, et un tableau
                                         associatif « moyen => valeur ». La
                                         seconde perdait son étiquette.

                                         Et un numéro se compose : sur un
                                         téléphone, c'est l'action principale
                                         d'une fiche prestataire, elle était
                                         posée en texte mort. --}}
                                    @foreach ($providerProfile->contact_methods as $moyen => $contact)
                                        @php
                                            $etiquette = is_string($moyen) ? Str::of($moyen)->replace('_', ' ')->ucfirst().' : ' : '';
                                            $chiffres = preg_replace('/\D+/', '', (string) $contact);
                                        @endphp
                                        <li>
                                            {{ $etiquette }}
                                            @if (strlen($chiffres) >= 8)
                                                <a href="tel:{{ $chiffres }}" class="font-label-numeric font-medium text-primary hover:text-primary-container">{{ $contact }}</a>
                                            @else
                                                {{ $contact }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($adresse)
                            <div>
                                <h3 class="mb-2 flex items-center gap-2 font-button-text font-semibold text-on-surface">
                                    <x-icon name="location_on" class="text-on-surface-variant" />
                                    {{ __("Adresse") }}
                                </h3>
                                <p class="text-on-surface-variant">{{ $adresse }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($aUneCarte)
                    <div id="provider-map-show" class="z-0 h-48 w-full overflow-hidden rounded-xl border border-outline-variant shadow-elevation-1 md:h-64"></div>
                @endif
            </section>
        @endif

        <section>
            <h2 class="mb-4 flex items-center gap-2 font-headline-lg text-headline-lg text-on-surface">
                <x-icon name="home_repair_service" class="text-primary" />
                {{ __("Ses services") }}
            </h2>

            @if ($services->isEmpty())
                <x-empty-state :title="__('Ce prestataire n\'a pas encore publié de service.')" />
            @else
                {{--
                    Une grille de vignettes, et non la rangée de liste
                    `x-service-card` employée partout ailleurs. Deux raisons :
                    la liste répète sous chaque ligne le nom du prestataire et
                    sa coche de vérification — sur sa propre fiche, c'est la
                    même information trois fois — et il y a ici quelques
                    services, pas quinze résultats de recherche à parcourir.
                --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <a href="{{ route('services.show', $service) }}"
                           class="group flex h-full flex-col justify-between rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-5 transition-colors hover:border-primary hover:shadow-elevation-2">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary">{{ $service->title }}</h3>
                                    @unless ($service->is_available)
                                        <span class="shrink-0 rounded-full bg-surface-container px-2 py-0.5 font-label-sm text-label-sm font-semibold uppercase tracking-wide text-on-surface-variant">
                                            {{ __("Indisponible") }}
                                        </span>
                                    @endunless
                                </div>

                                @if ($service->description)
                                    <p class="mt-2 line-clamp-2 text-on-surface-variant">{{ $service->description }}</p>
                                @endif
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-outline-variant/50 pt-4">
                                <span class="font-label-numeric text-label-numeric font-bold text-primary">
                                    @if ($service->price_amount)
                                        {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                        @if ($service->price_unit)
                                            <span class="font-body-md text-label-md font-normal text-on-surface-variant">/ {{ $service->price_unit }}</span>
                                        @endif
                                    @else
                                        <span class="font-body-md text-label-md font-normal text-on-surface-variant">{{ __("Prix à convenir") }}</span>
                                    @endif
                                </span>
                                <x-icon name="arrow_forward" class="text-primary" />
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">{{ $services->links() }}</div>
            @endif
        </section>

        <section>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-headline-lg text-headline-lg text-on-surface">
                    <x-icon name="reviews" class="text-primary" />
                    {{ __("Avis clients") }}
                </h2>
                @if ($providerProfile->rating_count)
                    <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" />
                @endif
            </div>

            @if ($reviews->isEmpty())
                <x-empty-state :title="__('Aucun avis pour le moment.')"
                               :description="__('Les avis sont laissés par les clients après une prestation terminée.')" />
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($reviews as $review)
                        <article class="rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-semibold text-on-surface">
                                    {{ $review->client?->clientProfile?->fullName() ?? $review->client?->name }}
                                </span>
                                <x-star-rating :rating="$review->rating" :afficherNote="false" />
                            </div>
                            @if ($review->comment)
                                <p class="mt-2 leading-relaxed text-on-surface">{{ $review->comment }}</p>
                            @endif
                            <p class="mt-2 font-label-numeric text-label-sm text-on-surface-variant">{{ $review->created_at->format('d/m/Y') }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="action-bar fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="mx-auto max-w-container">
            @include('partials.request-action', [
                'href' => route('requests.create', ['provider_id' => $providerProfile->user_id]),
                'label' => __('Envoyer une demande'),
                'compactLabel' => 'Envoyer une demande',
                'compact' => true,
            ])
        </div>
    </div>

    @if ($aUneCarte)
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
