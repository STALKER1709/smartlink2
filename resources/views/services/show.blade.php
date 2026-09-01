@php
    /*
     * La fiche de service est la page qu'on colle dans une conversation
     * WhatsApp : son aperçu doit dire le métier, la ville et le prix, sinon
     * le lien n'annonce rien.
     */
    $villeDuService = $service->city;

    $titreSeo = $villeDuService
        ? __('seo.service', ['titre' => $service->title, 'ville' => $villeDuService])
        : __('seo.service_no_city', ['titre' => $service->title]);

    $prestataire = $service->provider->providerProfile?->business_name
        ?? $service->provider->name;

    $tarif = $service->price_amount
        ? number_format((float) $service->price_amount, 0, ',', ' ').' FCFA'
            .($service->price_unit ? ' / '.$service->price_unit : '')
        : null;

    $descriptionSeo = implode(' · ', array_filter([
        $prestataire,
        $tarif,
        Str::limit($service->description, 100),
    ]));

    $imageSeo = $service->images->isNotEmpty()
        ? media_url($service->images->first()->path)
        : null;
@endphp

<x-app-layout :titre="$titreSeo" :description="$descriptionSeo" :image-partage="$imageSeo">
    @php
        $profile = $service->provider->providerProfile;
        $prix = $service->price_amount
            ? number_format((float) $service->price_amount, 0, ',', ' ').' FCFA'
            : null;
    @endphp

    <div class="mx-auto max-w-container px-margin-mobile py-6 pb-28 md:px-margin-tablet lg:px-margin-desktop md:py-8 lg:pb-8">
        <a href="{{ route('services.index') }}" class="mb-4 inline-flex min-h-6 items-center gap-1 text-label-md text-on-surface-variant hover:text-primary sm:hidden">
            <x-icon name="arrow_back" />
            {{ __("Tous les services") }}
        </a>

        <nav class="mb-4 hidden min-h-6 items-center gap-1 text-label-md text-on-surface-variant sm:flex">
            <a href="{{ route('services.index') }}" class="inline-flex min-h-6 items-center hover:text-primary">{{ __("Services") }}</a>
            <x-icon name="chevron_right" />
            @if ($service->category)
                <a href="{{ route('services.index', ['category_id' => $service->category_id]) }}" class="inline-flex min-h-6 items-center hover:text-primary">{{ $service->category->name }}</a>
                <x-icon name="chevron_right" />
            @endif
            <span class="truncate text-on-surface">{{ $service->title }}</span>
        </nav>

        {{-- L'en-tête de page, au-dessus des deux colonnes comme dans la
             maquette : titre, puis la ligne qui dit où, combien d'avis, et ce
             que coûte la mise en relation. Il était auparavant sous la
             galerie, dans la colonne de gauche — le visiteur découvrait donc
             cinq cents pixels de photo avant de savoir ce qu'il regardait. --}}
        <header class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-2">
                @if ($service->category)
                    <a href="{{ route('services.index', ['category_id' => $service->category_id]) }}"
                       class="rounded-full bg-primary-container/20 px-3 py-1 font-label-sm text-label-sm font-semibold text-primary hover:bg-primary-container/30">{{ $service->category->name }}</a>
                @endif
                <x-status-badge :status="$service->is_available ? 'active' : 'inactive'" />
                <x-promoted-badge :profile="$profile" />
            </div>

            <h1 class="font-display-lg text-headline-lg text-on-surface md:text-display-lg">{{ $service->title }}</h1>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 font-label-md text-label-md text-on-surface-variant">
                @if ($service->city)
                    <span class="flex items-center gap-1">
                        <x-icon name="location_on" size="sm" />
                        {{-- « location » est une adresse libre : quand le
                             prestataire y a simplement retapé son quartier,
                             l'afficher donnait « Douala, Bonamoussadi ·
                             Bonamoussadi ». --}}
                        {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif
                        @if ($service->location && $service->location !== $service->quarter) · {{ $service->location }} @endif
                    </span>
                @endif

                @if ($profile?->rating_count)
                    <x-star-rating :rating="$profile->rating_avg" :count="$profile->rating_count" compact />
                @endif

                {{-- La maquette place ici un badge « paiement sécurisé ». Aucun
                     paiement ne passe par SmartLink : la garantie qu'on peut
                     réellement afficher est celle-là. --}}
                <span class="flex items-center gap-1 text-primary">
                    <x-icon name="verified_user" size="sm" />
                    {{ __("Aucune commission") }}
                </span>
            </div>
        </header>

        <div class="mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-8">
                {{-- Une image maîtresse plutôt qu'une mosaïque : c'est elle qui
                     donne envie, les autres ne sont que des compléments.

                     Elle passe par `x-service-thumb`, qui empile la scène
                     dessinée, la photo du métier et celle du prestataire, et
                     retire chaque couche qui échoue. Écrite en `<img>` seule,
                     elle laissait le rectangle cassé du navigateur dès qu'un
                     fichier manquait, et un pictogramme pâle sur un aplat gris
                     quand le prestataire n'avait rien déposé. --}}
                <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-elevation-1">
                    <div class="relative aspect-[16/9] bg-surface-container-high">
                        <x-service-thumb :service="$service" />
                    </div>

                    @if ($service->images->count() > 1)
                        <div class="grid grid-cols-4 gap-1 p-1 sm:grid-cols-6">
                            @foreach ($service->images->skip(1) as $image)
                                <img src="{{ media_url($image->path) }}" alt="" loading="lazy" class="aspect-square w-full rounded-lg object-cover" onerror="this.remove()">
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($service->availability_note)
                    <p class="flex items-start gap-2 rounded-xl border border-outline-variant bg-secondary-container/20 px-4 py-3 font-body-md text-body-md text-on-surface shadow-elevation-1">
                        <x-icon name="schedule" class="shrink-0 text-secondary" />
                        {{ $service->availability_note }}
                    </p>
                @endif

                <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5 shadow-elevation-1 md:p-8">
                    <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("À propos de ce service") }}</h2>
                    <p class="prose-measure mt-4 whitespace-pre-line font-body-md text-body-lg leading-relaxed text-on-surface-variant">{{ $service->description }}</p>
                </section>
            </div>

            {{-- Colonne d'action, collante au défilement sur grand écran. Le
                 prestataire et le prix tiennent dans une seule carte, comme
                 dans la maquette : c'est la même décision — qui, et combien. --}}
            <aside class="flex flex-col gap-4 lg:sticky lg:top-24 lg:col-span-4 lg:self-start">
                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-5 shadow-elevation-1">
                    <div class="flex items-center gap-4 border-b border-outline-variant pb-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-surface-container-lowest bg-surface-container-high shadow-elevation-1">
                            @if ($profile->logo_path)
                                <img src="{{ media_url($profile->logo_path) }}" alt="" loading="lazy" class="h-full w-full object-cover" onerror="this.remove()">
                            @else
                                <span class="font-headline-md text-headline-md font-bold text-primary">{{ Str::upper(Str::substr($profile->business_name, 0, 1)) }}</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="flex items-start gap-1 font-headline-sm text-headline-sm leading-tight text-on-surface">
                                <a href="{{ route('providers.show', $profile) }}" class="rounded-lg hover:text-primary">{{ $profile->business_name }}</a>
                                @if ($profile->is_verified)
                                    <x-icon name="verified" size="sm" filled label="{{ __('Prestataire vérifié') }}" class="mt-1 shrink-0 text-primary" />
                                @endif
                            </h2>

                            @if ($profile->category)
                                <p class="mt-0.5 font-label-sm text-label-sm text-on-surface-variant">{{ $profile->category->name }}</p>
                            @endif

                            <div class="mt-1">
                                @if ($profile->rating_count)
                                    <x-star-rating :rating="$profile->rating_avg" :count="$profile->rating_count" compact />
                                @else
                                    <span class="font-label-sm text-label-sm text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Le prix ne s'affiche ici qu'à partir de `lg` : sous ce
                         palier, la barre d'action fixée du bas le porte déjà,
                         et le répéter poussait l'action hors de l'écran. --}}
                    <div class="hidden pt-4 lg:block">
                        <p class="font-label-sm text-label-sm font-semibold uppercase tracking-wide text-on-surface-variant">{{ __("Prix indicatif") }}</p>
                        <p class="mt-1 font-label-numeric text-headline-md text-primary">
                            @if ($prix)
                                {{ $prix }}@if ($service->price_unit)<span class="font-label-numeric text-label-md text-on-surface-variant"> / {{ $service->price_unit }}</span>@endif
                            @else
                                <span class="font-body-md text-body-md text-on-surface-variant">{{ __("Prix à convenir") }}</span>
                            @endif
                        </p>
                        <p class="mt-1 font-body-md text-label-sm text-on-surface-variant">{{ __("Prix indicatif — le montant se convient avec le prestataire.") }}</p>

                        <div class="mt-4">
                            @include('partials.request-action', [
                                'href' => route('requests.create', ['service_id' => $service->id]),
                                'label' => __('Faire une demande'),
                            ])
                        </div>
                    </div>

                    <x-secondary-button :href="route('providers.show', $profile)" class="mt-4 w-full">
                        {{ __("Voir le profil du prestataire") }}
                    </x-secondary-button>
                </div>

                <p class="flex items-start gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 font-body-md text-label-sm leading-relaxed text-on-surface-variant shadow-elevation-1">
                    <x-icon name="shield" class="shrink-0 text-primary" />
                    {{ __("SmartLink ne prend aucune commission et ne perçoit aucun paiement : le règlement se convient directement avec le prestataire.") }}
                </p>
            </aside>
        </div>

        {{-- « Services similaires » était dans la colonne principale, donc
             au-dessus de la colonne d'action sur mobile : le visiteur lisait
             la description, puis quatre annonces concurrentes, et ne
             rencontrait le prestataire de *ce* service qu'après. Le bloc
             descend sous la grille — pleine largeur sur grand écran par la
             même occasion. --}}
        @if ($relatedServices->isNotEmpty())
            <div class="mt-12 border-t border-outline-variant pt-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("Services similaires à proximité") }}</h2>
                    <a href="{{ route('services.index', ['category_id' => $service->category_id]) }}"
                       class="flex min-h-6 items-center gap-1 font-label-md text-label-md text-primary hover:underline">
                        {{ __("Tout le métier") }}
                        <x-icon name="chevron_right" size="sm" />
                    </a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedServices as $related)
                        <x-service-card :service="$related" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Sur mobile, l'action reste sous la main : sans cette barre, elle se
         trouvait tout en bas d'une page longue. --}}
    <div class="action-bar fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-container items-center gap-3">
            <div class="min-w-0 flex-1">
                <div class="truncate font-label-numeric text-body-md text-on-surface">
                    {{ $prix ?? 'Prix à convenir' }}
                    @if ($prix && $service->price_unit)
                        <span class="font-body-md text-label-sm text-on-surface-variant">/ {{ $service->price_unit }}</span>
                    @endif
                </div>
                <p class="truncate text-label-sm text-on-surface-variant">{{ $profile->business_name }}</p>
            </div>
            <div class="shrink-0">
                @include('partials.request-action', [
                    'href' => route('requests.create', ['service_id' => $service->id]),
                    'label' => __('Faire une demande'),
                    'compactLabel' => 'Demander',
                    'compact' => true,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
