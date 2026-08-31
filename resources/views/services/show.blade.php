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
        <a href="{{ route('services.index') }}" class="mb-4 inline-flex items-center gap-1 text-label-md text-on-surface-variant hover:text-primary sm:hidden">
            <x-icon name="arrow_back" />
            {{ __("Tous les services") }}
        </a>

        <nav class="mb-4 hidden items-center gap-1 text-label-md text-on-surface-variant sm:flex">
            <a href="{{ route('services.index') }}" class="hover:text-primary">{{ __("Services") }}</a>
            <x-icon name="chevron_right" />
            @if ($service->category)
                <a href="{{ route('services.index', ['category_id' => $service->category_id]) }}" class="hover:text-primary">{{ $service->category->name }}</a>
                <x-icon name="chevron_right" />
            @endif
            <span class="truncate text-on-surface">{{ $service->title }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                {{-- Une image maîtresse plutôt qu'une mosaïque : c'est elle qui
                     donne envie, les autres ne sont que des compléments. --}}
                <div class="overflow-hidden rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest">
                    <div class="aspect-[16/9] bg-surface-container-high">
                        @if ($service->images->isNotEmpty())
                            <img src="{{ media_url($service->images->first()->path) }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <x-category-icon :icon="$service->category?->icon" class="text-6xl text-primary/30" />
                            </div>
                        @endif
                    </div>

                    @if ($service->images->count() > 1)
                        <div class="grid grid-cols-4 gap-1 p-1 sm:grid-cols-6">
                            @foreach ($service->images->skip(1) as $image)
                                <img src="{{ media_url($image->path) }}" alt="" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($service->category)
                            <span class="rounded-full bg-primary-container/20 px-3 py-1 text-label-sm font-semibold text-primary">{{ $service->category->name }}</span>
                        @endif
                        <x-status-badge :status="$service->is_available ? 'active' : 'inactive'" />
                        <x-promoted-badge :profile="$profile" />
                    </div>

                    <h1 class="mt-3 font-headline-lg text-headline-lg text-on-surface">{{ $service->title }}</h1>

                    @if ($service->city)
                        <p class="mt-2 flex items-center gap-1 text-label-md text-on-surface-variant">
                            <x-icon name="location_on" />
                            {{-- « location » est une adresse libre : quand le
                                 prestataire y a simplement retapé son quartier,
                                 l'afficher donnait « Douala, Bonamoussadi ·
                                 Bonamoussadi ». --}}
                            {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif
                            @if ($service->location && $service->location !== $service->quarter) · {{ $service->location }} @endif
                        </p>
                    @endif

                    @if ($service->availability_note)
                        <p class="mt-4 flex items-start gap-2 rounded-xl border border-outline-variant shadow-elevation-1 bg-tertiary-container/15 px-4 py-3 text-label-md text-on-surface">
                            <x-icon name="schedule" class="text-tertiary" />
                            {{ $service->availability_note }}
                        </p>
                    @endif

                    <h2 class="mt-8 font-headline-md text-headline-md text-on-surface">{{ __("Description") }}</h2>
                    <p class="prose-measure mt-3 whitespace-pre-line leading-relaxed text-on-surface">{{ $service->description }}</p>
                </div>

            </div>

            {{-- Colonne d'action, collante au défilement sur grand écran. --}}
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div class="hidden rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-5 lg:block">
                    <div class="font-label-numeric text-headline-md text-on-surface">
                        @if ($prix)
                            {{ $prix }}
                            @if ($service->price_unit)
                                <span class="font-body-md text-body-md text-on-surface-variant">/ {{ $service->price_unit }}</span>
                            @endif
                        @else
                            <span class="font-body-md text-body-md text-on-surface-variant">{{ __("Prix à convenir") }}</span>
                        @endif
                    </div>
                    <p class="mt-1 text-label-sm text-on-surface-variant">{{ __("Prix indicatif — le montant se convient avec le prestataire.") }}</p>

                    <div class="mt-4">
                        @include('partials.request-action', [
                            'href' => route('requests.create', ['service_id' => $service->id]),
                            'label' => __('Faire une demande'),
                        ])
                    </div>
                </div>

                {{-- Une rangée seule a besoin d'un cadre : son filet du bas
                     n'a rien à séparer, et le `[&>a]:border-0` le retire. --}}
                <div class="mt-4 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest px-4 pb-2 [&>a]:border-0">
                    <p class="pt-4 text-label-sm font-semibold uppercase tracking-wide text-on-surface-variant">{{ __("Le prestataire") }}</p>
                    <x-provider-card :provider-profile="$profile" />
                </div>

                <p class="mt-4 flex items-start gap-2 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-low px-4 py-3 text-label-sm leading-relaxed text-on-surface-variant">
                    <x-icon name="shield" class="text-primary" />
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
            <div class="mt-12">
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("Services similaires") }}</h2>
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
