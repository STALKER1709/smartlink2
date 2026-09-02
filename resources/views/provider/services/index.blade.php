@php
    $plan = auth()->user()->currentPlan();
    $plafond = $plan && ! $plan->allowsUnlimitedServices() ? $plan->max_services : null;

    $onglets = [
        'tous' => __('Tous'),
        'actifs' => __('Actifs'),
        'pause' => __('En pause'),
    ];
@endphp

<x-app-layout :titre="__('Mes services')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Mes services')">
            <x-slot name="subtitle">
                <span class="flex items-center gap-2 font-body-md text-body-md text-on-surface-variant">
                    <x-icon name="inventory_2" />
                    <span>
                        {{ __('Gérez vos annonces et suivez ce qu\'elles rapportent.') }}
                        @if ($plafond !== null)
                            <span class="font-label-numeric">{{ $compteurs['tous'] }}</span> {{ __('sur') }}
                            <span class="font-label-numeric">{{ $plafond }}</span> {{ trans_choice('autorisé|autorisés', $plafond) }}.
                        @endif
                    </span>
                </span>
            </x-slot>
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto w-full max-w-container px-margin-mobile py-8 md:px-margin-tablet lg:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-outline-variant bg-primary-container/15 px-4 py-3 text-label-md text-primary">
                {{ session('status') }}
            </div>
        @endif

        {{-- Les trois onglets de la maquette. Ils défilent horizontalement sur
             mobile plutôt que de passer à la ligne : trois libellés suivis de
             leur compteur tiennent mal sur 360 px. --}}
        @if ($compteurs['tous'] > 0)
            <div class="hide-scrollbar -mx-margin-mobile mb-6 flex gap-4 overflow-x-auto border-b border-outline-variant px-margin-mobile md:mx-0 md:px-0">
                @foreach ($onglets as $cle => $libelle)
                    @php $courant = $statut === $cle; @endphp
                    <a href="{{ route('provider.services.index', $cle === 'tous' ? [] : ['statut' => $cle]) }}"
                       @class([
                           'flex shrink-0 items-center gap-1 whitespace-nowrap border-b-2 px-1 pb-2 pt-1 font-label-md text-label-md transition-colors',
                           'border-primary text-primary' => $courant,
                           'border-transparent text-on-surface-variant hover:text-on-surface' => ! $courant,
                       ])
                       @if ($courant) aria-current="page" @endif>
                        {{ $libelle }}
                        <span class="font-label-numeric text-label-sm">({{ $compteurs[$cle] }})</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($services->isEmpty())
            @php
                // Calculé ici plutôt que dans une expression ternaire posée
                // en attribut du composant : sur plusieurs lignes, elle se lit
                // mal et on ne voit plus lequel des deux textes va sortir.
                $vide = $statut === 'tous'
                    ? [
                        'titre' => __("Vous n'avez pas encore publié de service."),
                        'texte' => __('Un service bien décrit, avec une photo et un prix indicatif, est ce qui décide un client à vous écrire.'),
                        'action' => __('Publier mon premier service'),
                        'lien' => route('provider.services.create'),
                    ]
                    : [
                        'titre' => __('Aucun service dans cet onglet.'),
                        'texte' => __('Vos autres annonces sont dans les onglets voisins.'),
                        'action' => __('Voir tous mes services'),
                        'lien' => route('provider.services.index'),
                    ];
            @endphp

            <x-empty-state :title="$vide['titre']" :description="$vide['texte']"
                           :action-label="$vide['action']" :action-href="$vide['lien']" />
        @else
            {{-- La grille de cartes de la maquette : grande vignette, état posé
                 dessus, menu d'actions dans l'angle, et le pied qui porte les
                 deux seuls chiffres qui disent si une annonce travaille. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($services as $service)
                    @php $actif = $service->status === \App\Models\Service::STATUS_ACTIVE && $service->is_available; @endphp

                    <article class="flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-elevation-1 transition-shadow duration-150 hover:shadow-elevation-2">
                        <div class="relative h-48 w-full shrink-0 overflow-hidden bg-surface-container-high">
                            <x-service-thumb :service="$service" />

                            <span @class([
                                'absolute left-3 top-3 flex items-center gap-1 rounded-full px-2 py-1 font-label-sm text-label-sm font-semibold shadow-elevation-1',
                                'bg-primary text-on-primary' => $actif,
                                'bg-surface-container-highest text-on-surface-variant' => ! $actif,
                            ])>
                                <x-icon :name="$actif ? 'check_circle' : 'pause_circle'" size="xs" filled />
                                {{ $actif ? __('Actif') : __('En pause') }}
                            </span>

                            {{-- Les trois actions sous un menu, comme dans la
                                 maquette. Alignées en clair sous la carte,
                                 « Supprimer » avait le même poids visuel que
                                 « Modifier » — et c'est irréversible. --}}
                            {{-- Le composant de menu ne fusionne pas les
                                 attributs reçus : sa classe de position était
                                 ignorée, et le bouton se posait sous la
                                 vignette au lieu de son angle. C'est
                                 l'enveloppe qui le place. --}}
                            <div class="absolute right-3 top-3 z-10">
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button type="button"
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-lowest/90 text-on-surface shadow-elevation-1 backdrop-blur transition-colors hover:bg-surface-container-lowest"
                                            aria-label="{{ __('Actions sur « :titre »', ['titre' => $service->title]) }}">
                                        <x-icon name="more_vert" size="sm" />
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('services.show', $service)">{{ __("Voir l'annonce") }}</x-dropdown-link>
                                    <x-dropdown-link :href="route('provider.services.edit', $service)">{{ __("Modifier") }}</x-dropdown-link>

                                    <form action="{{ route('provider.services.destroy', $service) }}" method="POST"
                                          onsubmit="return confirm('Supprimer « {{ $service->title }} » ? Cette action est définitive.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="block w-full px-4 py-2 text-start font-label-md text-label-md text-error transition-colors hover:bg-surface-container">
                                            {{ __("Supprimer") }}
                                        </button>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-4">
                            <p class="font-label-sm text-label-sm font-semibold uppercase tracking-wide text-primary">{{ $service->category?->name }}</p>

                            <h2 class="mt-1 font-headline-sm text-headline-sm font-bold leading-snug text-on-surface">
                                <a href="{{ route('provider.services.edit', $service) }}" class="rounded-lg hover:text-primary">{{ $service->title }}</a>
                            </h2>

                            <p class="mt-2 line-clamp-2 font-body-md text-body-md text-on-surface-variant">{{ $service->description }}</p>

                            <div class="mt-4 grid grid-cols-2 gap-4 border-t border-outline-variant pt-4">
                                {{-- La maquette annonce « Vues (30j) ». Le
                                     compteur du dépôt est cumulé depuis la
                                     publication : l'étiquette dit ce qu'elle
                                     compte réellement. --}}
                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant">{{ __("Vues") }}</p>
                                    <p class="mt-0.5 flex items-center gap-1 font-label-numeric text-label-numeric font-bold text-on-surface">
                                        <x-icon name="visibility" size="sm" class="text-outline" />
                                        {{ $service->views_count }}
                                    </p>
                                </div>

                                <div>
                                    <p class="font-label-sm text-label-sm text-on-surface-variant">{{ __("Demandes") }}</p>
                                    <p class="mt-0.5 flex items-center gap-1 font-label-numeric text-label-numeric font-bold text-primary">
                                        <x-icon name="forum" size="sm" />
                                        {{ $service->requests_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            @if ($service->price_amount)
                                <p class="mt-3 font-label-numeric text-label-numeric text-primary">
                                    {{ number_format((float) $service->price_amount, 0, ',', ' ') }} FCFA
                                    @if ($service->price_unit)<span class="text-on-surface-variant">/ {{ $service->price_unit }}</span>@endif
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
