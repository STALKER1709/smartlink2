<x-app-layout>
    {{-- ══ Hero ══ La recherche en langage naturel est notre différence, et elle
         est ouverte aux visiteurs : c'est elle qui prend la place centrale. --}}
    <section class="relative overflow-hidden bg-primary">
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white"></div>
            <div class="absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-white"></div>
        </div>

        <div class="relative mx-auto max-w-container px-margin-mobile py-14 md:px-margin-desktop md:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="font-headline-xl text-headline-xl text-white">
                    Un artisan de confiance, près de chez vous
                </h1>
                <p class="mx-auto mt-4 max-w-2xl font-body-lg text-body-lg text-white/85">
                    Plombiers, électriciens, coiffeuses, répétiteurs — décrivez votre besoin,
                    nous trouvons le prestataire. <strong class="font-semibold text-white">Gratuit pour les clients.</strong>
                </p>

                <x-natural-search hero class="mx-auto mt-8 max-w-2xl" />

                @if ($categories->isNotEmpty())
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                        @foreach ($categories->take(6) as $category)
                            <a
                                href="{{ route('services.index', ['category_id' => $category->id]) }}"
                                class="rounded-full border border-white/30 px-3.5 py-1.5 text-sm font-medium text-white transition-colors hover:bg-white/15"
                            >
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($providerCount > 0)
                    <p class="mt-8 font-label-numeric text-label-numeric text-white/80">
                        {{ $providerCount }} {{ Str::plural('prestataire', $providerCount) }}
                        · {{ $serviceCount }} {{ Str::plural('service', $serviceCount) }} en ligne
                    </p>
                @endif
            </div>
        </div>
    </section>

    {{-- ══ Prestataires vérifiés ══ --}}
    @if ($featuredProviders->isNotEmpty())
        <section class="bg-surface-container-low">
            <div class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
                <x-section-header title="Prestataires vérifiés"
                                  subtitle="Pièce d'identité contrôlée par notre équipe."
                                  :href="route('providers.index', ['verified_only' => 1])"
                                  link-label="Voir l'annuaire" />

                {{-- Même régime que les services juste dessous : deux motifs
                     pour le même travail sur un seul écran, c'était un de
                     trop. Le filet appartient à la rangée, jamais au
                     conteneur — `divide-y` le remet à zéro sur tous les
                     enfants sauf le premier dès qu'on passe en deux
                     colonnes. --}}
                <div class="mt-4 -mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6 lg:grid lg:grid-cols-2 lg:gap-x-10">
                    @foreach ($featuredProviders as $providerProfile)
                        <x-provider-card :provider-profile="$providerProfile" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══ Services récents ══ --}}
    <section class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
        <x-section-header title="Derniers services publiés" :href="route('services.index')" />

        @if ($recentServices->isEmpty())
            <x-empty-state class="mt-6" title="Aucun service disponible pour le moment." />
        @else
            <div class="mt-4 -mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6 lg:grid lg:grid-cols-2 lg:gap-x-10">
                @foreach ($recentServices as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ══ Catégories ══ --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
            <x-section-header title="Explorer par métier" :href="route('services.index')" link-label="Tous les services" />

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                {{-- Six métiers suffisent sur un téléphone : les six suivants
                     ajoutaient trois rangées à une page déjà longue, et
                     « Tous les services » mène de toute façon à la liste
                     complète. --}}
                @foreach ($categories->take(12) as $category)
                    <a
                        href="{{ route('services.index', ['category_id' => $category->id]) }}"
                        @class([
                            'group flex-col items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-4 text-center transition-colors hover:border-primary/50 hover:bg-surface-container-low',
                            'flex' => $loop->index < 6,
                            'hidden sm:flex' => $loop->index >= 6,
                        ])
                    >
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-container/40 text-primary transition-colors group-hover:bg-primary group-hover:text-on-primary">
                            <x-category-icon :icon="$category->icon" class="text-2xl" />
                        </span>
                        <span class="text-sm font-semibold leading-tight text-on-surface">{{ $category->name }}</span>
                        <span class="font-label-numeric text-xs text-on-surface-variant">
                            {{ $category->services_count }} {{ Str::plural('service', $category->services_count) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══ Comment ça marche ══ Le modèle du produit n'est évident pour personne :
         le client ne paie rien ici, et le règlement se convient de gré à gré. --}}
    <section class="border-b border-outline-variant bg-surface-container-low">
        <div class="mx-auto max-w-container px-margin-mobile py-12 md:px-margin-desktop">
            <h2 class="text-center font-headline-lg text-headline-lg text-on-surface">Comment ça marche</h2>

            <ol class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ([
                    ['search', 'Décrivez votre besoin', 'Une phrase suffit. La catégorie, la ville et le quartier sont reconnus automatiquement.'],
                    ['forum', 'Comparez et contactez', 'Notes, avis et prix indicatifs sont affichés. Vous échangez directement avec le prestataire.'],
                    ['handshake', 'Convenez entre vous', "Le règlement se fait de gré à gré, hors plateforme. SmartLink ne prend aucune commission."],
                ] as $index => [$icon, $titre, $texte])
                    <li class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container/20 text-primary">
                                <span class="material-symbols-outlined">{{ $icon }}</span>
                            </span>
                            <span class="font-label-numeric text-label-numeric text-on-surface-variant">0{{ $index + 1 }}</span>
                        </div>
                        <h3 class="mt-4 font-headline-md text-base font-semibold text-on-surface">{{ $titre }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ══ Appel aux prestataires ══ --}}
    @guest
        <section class="bg-inverse-surface">
            <div class="mx-auto max-w-container px-margin-mobile py-14 text-center md:px-margin-desktop">
                <h2 class="font-headline-lg text-headline-lg text-inverse-on-surface">Vous êtes prestataire de services ?</h2>
                <p class="mx-auto mt-3 max-w-xl font-body-md text-body-md text-inverse-on-surface/80">
                    Publiez vos services, recevez des demandes, développez votre clientèle.
                    <strong class="font-semibold text-inverse-on-surface">30 jours d'essai gratuit</strong>, sans engagement.
                </p>
                <a href="{{ route('register') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                    Créer un compte prestataire
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </section>
    @endguest
</x-app-layout>
