{{-- La page d'accueil garde le titre par défaut, qui porte déjà la
     promesse et le pays. --}}
<x-app-layout :description="__('seo.default_description')">
    {{-- ══ Hero ══
         La maquette pose le titre et la recherche sur le fond de page, sans
         le bandeau vert qui les portait. Deux gains : la recherche — notre
         différence, et le seul contrôle de l'écran — cesse d'être un objet
         blanc posé sur une couleur pour redevenir un champ, et les pastilles
         de métiers du bandeau disparaissent au profit de la grille juste
         dessous, qui disait déjà la même chose vingt lignes plus bas. --}}
    <section class="border-b border-outline-variant bg-surface-container-low">
        <div class="mx-auto max-w-container px-margin-mobile py-12 text-center md:px-margin-desktop md:py-16">
            <h1 class="font-headline-xl text-headline-xl text-on-background">
                {!! __('Un artisan de confiance,<br class="hidden sm:inline"> près de chez vous') !!}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl font-body-lg text-body-lg text-on-surface-variant">
                {{ __('Plombiers, électriciens, coiffeuses, répétiteurs — décrivez votre besoin, nous trouvons le prestataire.') }}
                <strong class="font-semibold text-primary">{{ __('Gratuit pour les clients.') }}</strong>
            </p>

            <x-natural-search class="mx-auto mt-8 max-w-2xl" />

            @if ($providerCount > 0)
                <p class="mt-6 font-label-lg text-label-lg text-on-surface-variant">
                    {{ trans_choice(':count prestataire|:count prestataires', $providerCount) }}
                    · {{ trans_choice(':count service en ligne|:count services en ligne', $serviceCount) }}
                </p>
            @endif
        </div>
    </section>

    {{-- ══ Catégories populaires ══
         Remontées juste sous la recherche, comme la maquette : c'est le second
         chemin d'entrée, et il valait mieux que le bas de page où il était.

         Huit métiers sur grand écran — deux rangées pleines à quatre colonnes.
         Quatre seulement sur téléphone : à huit, la grille posait quatre
         rangées et repoussait les prestataires d'un demi-écran, sur une page
         qui en fait déjà plus de cinq mille pixels. --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
            <x-section-header :title="__('Catégories populaires')" :href="route('services.index')" :link-label="__('Tous les services')" />

            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($categories->take(8) as $category)
                    <a href="{{ route('services.index', ['category_id' => $category->id]) }}"
                       @class([
                           'group flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest text-center transition-colors hover:border-primary/50',
                           'flex' => $loop->index < 4,
                           'hidden sm:flex' => $loop->index >= 4,
                       ])>
                        {{-- Le bandeau photographique, quand il existe. La
                             pastille du pictogramme reste et chevauche son bord
                             bas : elle est le repli si l'hôte ne répond pas, et
                             elle rattache la vignette au reste du site, où le
                             métier se lit toujours au même dessin. --}}
                        @php($photo = image_categorie($category->name))

                        <div @class(['relative w-full', 'h-24 bg-secondary-container/30' => (bool) $photo])>
                            @if ($photo)
                                <img src="{{ $photo['url'] }}" alt="" loading="lazy" referrerpolicy="no-referrer"
                                     @if ($photo['credit']) title="Photo : {{ $photo['credit'] }}" @endif
                                     class="h-full w-full object-cover" onerror="this.remove()">
                            @endif

                            <span @class([
                                'flex h-12 w-12 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container transition-colors group-hover:bg-primary group-hover:text-on-primary',
                                'absolute -bottom-6 left-1/2 -translate-x-1/2 border-2 border-surface-container-lowest' => (bool) $photo,
                                'mx-auto mt-6' => ! $photo,
                            ])>
                                <x-category-icon :icon="$category->icon" class="text-2xl" />
                            </span>
                        </div>

                        <div @class(['flex flex-1 flex-col justify-center gap-1 px-4 pb-6', 'pt-8' => (bool) $photo, 'pt-3' => ! $photo])>
                            <span class="font-medium leading-tight text-on-background">{{ $category->name }}</span>
                            <span class="font-label-numeric text-label-md text-on-surface-variant">
                                {{ $category->services_count }} {{ Str::plural('service', $category->services_count) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ Prestataires vérifiés ══
         La maquette veut des cartes ici, pas les rangées de liste employées
         partout ailleurs — et sur cet écran-là elle a raison : l'accueil doit
         convaincre un visiteur qui ne connaît pas SmartLink, et une rangée de
         liste ne montre ni visage ni prix d'entrée.

         Six prestataires, dont trois seulement sur téléphone : à six, la page
         posait mille trois cents pixels de cartes avant le premier service.
         À quatre et six colonnes les rangées sont pleines dans les deux cas.

         La note reste la puce ambre discrète du reste du site. La maquette la
         voulait en pastille orange pleine ; l'ambre est réservé aux
         avertissements — abonnement qui expire, plafond atteint — et une note
         de 4,8 n'en est pas un. --}}
    @if ($featuredProviders->isNotEmpty())
        <section class="bg-surface-container-low">
            <div class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
                <x-section-header :title="__('Prestataires vérifiés')"
                                  :subtitle="__('Pièce d\'identité contrôlée par notre équipe.')"
                                  :href="route('providers.index', ['verified_only' => 1])"
                                  link-label="Voir l'annuaire" />

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-gutter lg:grid-cols-3">
                    @foreach ($featuredProviders as $providerProfile)
                        <article @class([
                            'flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest',
                            'flex' => $loop->index < 3,
                            'hidden md:flex' => $loop->index >= 3,
                        ])>
                            <a href="{{ route('providers.show', $providerProfile) }}"
                               class="group flex flex-1 items-start gap-4 border-b border-outline-variant p-4 transition-colors hover:bg-surface-container-low/70">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-secondary-container/40">
                                    @if ($providerProfile->logo_path)
                                        <img src="{{ media_url($providerProfile->logo_path) }}" alt="" loading="lazy"
                                             class="h-full w-full object-cover" onerror="this.remove()">
                                    @else
                                        <span class="font-headline-md text-headline-md font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-headline-md text-headline-md leading-tight text-on-background group-hover:text-primary">
                                        {{ $providerProfile->business_name }}
                                    </h3>

                                    @if ($providerProfile->category)
                                        <p class="mt-0.5 text-on-surface-variant">{{ $providerProfile->category->name }}</p>
                                    @endif

                                    @if ($providerProfile->city)
                                        <p class="mt-1 flex items-center gap-1 text-label-lg text-on-surface-variant">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">location_on</span>
                                            {{ $providerProfile->city }}@if ($providerProfile->quarter), {{ $providerProfile->quarter }}@endif
                                        </p>
                                    @endif
                                </div>
                            </a>

                            <div class="flex flex-col gap-3 bg-surface-container-low p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    @if ($providerProfile->rating_count)
                                        <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" compact />
                                    @else
                                        <span class="text-label-lg text-on-surface-variant">{{ __('Pas encore d\'avis') }}</span>
                                    @endif

                                    {{-- Le montant en chasse fixe, la phrase qui le
                                         porte non : « À partir de » composé en
                                         JetBrains Mono ouvre un blanc de deux
                                         caractères avant le prix. --}}
                                    <span class="font-label-lg text-label-lg text-primary">
                                        @if ($providerProfile->min_price)
                                            {{ __('À partir de') }} <span class="font-label-numeric">{{ number_format((float) $providerProfile->min_price, 0, ',', ' ') }} FCFA</span>
                                        @else
                                            {{ __('Prix à convenir') }}
                                        @endif
                                    </span>
                                </div>

                                {{-- Le visiteur non connecté voit « Contacter »
                                     comme la maquette le veut : le middleware
                                     `auth` retient l'intention et
                                     `redirect()->intended()` le ramène à la
                                     demande après connexion. Seuls un
                                     prestataire ou un administrateur de
                                     passage, qui ne peuvent pas demander,
                                     voient la fiche à la place — sans quoi la
                                     page leur répétait six fois la même
                                     phrase grise. --}}
                                @if (auth()->guest() || auth()->user()->can('create', \App\Models\ServiceRequest::class))
                                    <a href="{{ route('requests.create', ['provider_id' => $providerProfile->user_id]) }}"
                                       class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-button-text font-semibold text-on-primary transition-colors hover:bg-primary-container">
                                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">mail</span>
                                        Contacter
                                    </a>
                                @else
                                    <a href="{{ route('providers.show', $providerProfile) }}"
                                       class="inline-flex w-full items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest px-6 py-3 font-button-text font-semibold text-on-surface transition-colors hover:border-primary/50">
                                        {{ __('Voir la fiche') }}
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══ Services récents ══ --}}
    <section class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
        <x-section-header :title="__('Derniers services publiés')" :href="route('services.index')" />

        @if ($recentServices->isEmpty())
            <x-empty-state class="mt-6" :title="__('Aucun service disponible pour le moment.')" />
        @else
            <div class="mt-4 -mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6 lg:grid lg:grid-cols-2 lg:gap-x-10">
                @foreach ($recentServices as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══ Comment ça marche ══ Le modèle du produit n'est évident pour personne :
         le client ne paie rien ici, et le règlement se convient de gré à gré. --}}
    <section class="border-b border-outline-variant bg-surface-container-low">
        <div class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
            <h2 class="text-center font-headline-lg text-headline-lg text-on-surface">{{ __('Comment ça marche') }}</h2>

            <ol class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ([
                    ['search', __('Décrivez votre besoin'), __('Une phrase suffit. La catégorie, la ville et le quartier sont reconnus automatiquement.')],
                    ['forum', __('Comparez et contactez'), __('Notes, avis et prix indicatifs sont affichés. Vous échangez directement avec le prestataire.')],
                    ['handshake', __('Convenez entre vous'), __('Le règlement se fait de gré à gré, hors plateforme. SmartLink ne prend aucune commission.')],
                ] as $index => [$icon, $titre, $texte])
                    <li class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container/20 text-primary">
                                <span class="material-symbols-outlined">{{ $icon }}</span>
                            </span>
                            <span class="font-label-numeric text-label-numeric text-on-surface-variant">0{{ $index + 1 }}</span>
                        </div>
                        <h3 class="mt-4 font-headline-sm text-headline-sm text-on-surface">{{ $titre }}</h3>
                        <p class="mt-2 text-label-lg leading-relaxed text-on-surface-variant">{{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ══ Appel aux prestataires ══ --}}
    @guest
        @php($photoCta = image_photo(config('imagery.cta')))

        {{-- La photo passe *derrière* le fond sombre, jamais à sa place : si
             l'hôte ne répond pas, le bandeau reste exactement ce qu'il était.
             Le voile n'est pas décoratif — sans lui, le texte blanc tombe sur
             une photo dont on ne connaît pas la luminosité. --}}
        <section class="relative overflow-hidden bg-inverse-surface">
            @if ($photoCta)
                {{-- Un seul assombrissement, pas deux : l'image à 30 % sous un
                     voile à 60 % ne laissait plus rien voir du tout — le
                     bandeau était noir et la requête vers l'hôte, payée pour
                     rien. --}}
                <img src="{{ $photoCta['url'] }}" alt="" loading="lazy" referrerpolicy="no-referrer"
                     @if ($photoCta['credit']) title="Photo : {{ $photoCta['credit'] }}" @endif
                     class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                <div class="absolute inset-0 bg-inverse-surface/75" aria-hidden="true"></div>
            @endif

            <div class="relative mx-auto max-w-container px-margin-mobile py-14 text-center md:px-margin-desktop">
                <h2 class="font-headline-lg text-headline-lg text-inverse-on-surface">{{ __('Vous êtes prestataire de services ?') }}</h2>
                <p class="mx-auto mt-3 max-w-xl font-body-md text-body-md text-inverse-on-surface/80">
                    {{ __('Publiez vos services, recevez des demandes, développez votre clientèle.') }}
                    <strong class="font-semibold text-inverse-on-surface">{{ __("30 jours d'essai gratuit") }}</strong>{{ __(', sans engagement.') }}
                </p>
                <a href="{{ route('register') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                    {{ __('Créer un compte prestataire') }}
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </section>
    @endguest
</x-app-layout>
