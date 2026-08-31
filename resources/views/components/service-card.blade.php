@props(['service'])

@php
    $profile = $service->provider?->providerProfile;
    $enseigne = $profile?->business_name ?? $service->provider?->name;
@endphp

{{--
    La carte de service, reprise de la maquette — et couchée sur mobile.

    Elle a d'abord été une rangée de liste ici, une décision prise quand rien
    ne remplissait la vignette et qu'une grille de rectangles gris ne
    hiérarchisait rien. Les scènes dessinées ont réglé ce défaut, et la
    maquette compose ses trois surfaces de services (accueil, catalogue,
    services similaires) en cartes.

    Debout sur téléphone, douze cartes faisaient une page de 6 500 px : on
    scrollait quatre écrans pour voir trois annonces. Elle est donc couchée
    sous `sm` — vignette à gauche, texte à droite — et se redresse au premier
    palier, là où la maquette la dessine. Un seul composant, deux formes.

    Un `<article>` et non un `<a>` : le cœur des favoris est un formulaire, et
    un formulaire dans un lien n'est pas du HTML valide. C'est le titre qui
    porte le lien, étendu à toute la carte par `after:inset-0` ; le cœur
    repasse au-dessus par son `z-20`.
--}}
<article
    {{ $attributes->merge(['class' => 'group relative flex overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-elevation-1 transition-shadow duration-150 hover:shadow-elevation-2 focus-within:shadow-elevation-2 sm:flex-col']) }}
>
    <div class="relative w-24 shrink-0 self-stretch overflow-hidden bg-surface-container-high sm:h-48 sm:w-full sm:self-auto">
        <x-service-thumb :service="$service" />

        {{-- Le métier en pastille sur la vignette, comme dans la maquette.
             Sur 112 px de large il n'y tiendrait pas : il repasse en ligne de
             texte au-dessus du titre, à sa place d'origine dans la rangée. --}}
        @if ($service->category?->name)
            <span class="absolute left-3 top-3 hidden rounded-full border border-outline-variant bg-surface-container-lowest/90 px-2 py-1 font-label-sm text-label-sm font-semibold text-on-surface shadow-elevation-1 backdrop-blur sm:inline-flex">
                {{ $service->category->name }}
            </span>
        @endif

        @unless ($service->is_available)
            <span class="absolute inset-x-0 bottom-0 bg-inverse-surface/85 px-2 py-1 text-center font-label-sm text-label-sm font-semibold uppercase tracking-wide text-inverse-on-surface">
                {{ __("Indisponible") }}
            </span>
        @endunless
    </div>

    {{-- Le cœur est posé sur la carte, pas sur la vignette : il tombe ainsi
         dans l'angle de l'image, comme dans la maquette.

         Il disparaît sous `sm`. Sur 390 px, ses 48 px de dégagement laissaient
         186 px de texte : « Douala » s'y abrégeait en « Dou… ». Le favori
         reste atteignable depuis la fiche du service et celle du prestataire,
         et il porte sur le prestataire, pas sur l'annonce. --}}
    @if ($profile)
        <x-favorite-button :provider-profile="$profile" contour class="absolute right-3 top-3 z-20 hidden sm:block" />
    @endif

    <div class="flex flex-1 flex-col p-3 sm:p-4">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            @if ($service->category?->name)
                <p class="font-label-sm text-label-sm font-semibold uppercase tracking-wide text-primary sm:hidden">{{ $service->category->name }}</p>
            @endif

            {{-- La mise en avant est ce que paie un prestataire Pro : elle
                 garde son badge entier. `PromotedBadgeTest` monte la garde. --}}
            <x-promoted-badge :profile="$profile" />
        </div>

        <h3 class="mt-1 font-headline-sm text-headline-sm font-bold leading-snug text-on-surface">
            <a href="{{ route('services.show', $service) }}"
               class="rounded-lg after:absolute after:inset-0 after:content-[''] group-hover:text-primary focus-visible:outline-none focus-visible:after:outline focus-visible:after:outline-2 focus-visible:after:outline-offset-2 focus-visible:after:outline-primary">
                {{ $service->title }}
            </a>
        </h3>

        <div class="mt-2 flex items-center gap-2">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-container-high font-label-sm text-label-sm font-bold text-on-surface">
                @if ($profile?->logo_path)
                    <img src="{{ media_url($profile->logo_path) }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                @else
                    {{ Str::upper(Str::substr($enseigne ?? 'S', 0, 1)) }}
                @endif
            </span>
            <span class="min-w-0 flex-1 truncate font-label-md text-label-md text-on-surface-variant">{{ $enseigne }}</span>
            @if ($profile?->is_verified)
                <x-icon name="verified" size="sm" filled label="{{ __('Prestataire vérifié') }}" class="shrink-0 text-primary" />
            @endif
        </div>

        @if ($profile?->rating_count)
            <div class="mt-2">
                <x-star-rating :rating="$profile->rating_avg" :count="$profile->rating_count" compact />
            </div>
        @endif

        <div class="mt-3 flex items-end justify-between gap-3 border-t border-outline-variant pt-3 sm:mt-4">
            <p class="flex min-w-0 items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
                <x-icon name="location_on" size="xs" class="shrink-0" />
                <span class="truncate">
                    @if ($service->city)
                        {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif
                    @else
                        {{ __("Lieu à convenir") }}
                    @endif
                </span>
            </p>

            <p class="shrink-0 text-right">
                @if ($service->price_amount)
                    {{-- L'unité est saisie par le prestataire, déjà rédigée
                         (« par heure ») : la préfixer donnait « Par par
                         heure ». --}}
                    <span class="block font-label-sm text-label-sm leading-none text-on-surface-variant">
                        {{ $service->price_unit ? Str::ucfirst($service->price_unit) : __('À partir de') }}
                    </span>
                    <span class="mt-1 block font-label-numeric text-body-lg font-bold leading-none text-primary">{{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA</span>
                @else
                    <span class="font-label-md text-label-md text-on-surface-variant">{{ __("Prix à convenir") }}</span>
                @endif
            </p>
        </div>
    </div>
</article>
