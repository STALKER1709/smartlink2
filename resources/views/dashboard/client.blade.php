@php
    $prenom = Str::of(auth()->user()->name)->trim()->explode(' ')->first();
@endphp

<x-app-layout :titre="__('Tableau de bord')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Bonjour, :prenom', ['prenom' => $prenom]).' 👋'"
                       :subtitle="__('Aperçu de vos activités.')" />
    </x-slot>

    <div class="mx-auto max-w-container space-y-8 px-margin-mobile py-8 md:px-margin-tablet lg:px-margin-desktop">
        {{-- Quatre chiffres, quatre pictogrammes : ce qui est en cours, ce qui
             est clos, ce qui attend une lecture, et ce que vous avez laissé
             derrière vous. --}}
        <x-stat-grid>
            <x-stat-tile :label="__('En cours')" icon="pending_actions" tone="secondary"
                         :value="($counts['accepted'] ?? 0) + ($counts['in_progress'] ?? 0)"
                         :href="route('requests.index', ['status' => 'in_progress'])" />
            <x-stat-tile :label="__('Terminées')" icon="check_circle" tone="primary"
                         :value="$counts['completed'] ?? 0"
                         :href="route('requests.index', ['status' => 'completed'])" />
            <x-stat-tile :label="__('Messages')" icon="mail" tone="error"
                         :value="$unreadMessages"
                         :href="route('conversations.index')" />
            <x-stat-tile :label="__('Avis laissés')" icon="star" tone="tertiary"
                         :value="$reviewsLeft" />
        </x-stat-grid>

        {{-- « Reprendre une recherche » : le client vient chercher quelqu'un.
             La barre de recherche appartient à son écran d'accueil, pas
             seulement à la page publique. --}}
        <section class="flex flex-col items-center gap-6 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-low p-6 md:flex-row">
            <form action="{{ route('services.index') }}" method="GET" class="w-full flex-1">
                <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("Reprendre une recherche") }}</h2>
                <div class="relative mt-4 w-full">
                    <x-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant" />
                    <label for="q" class="sr-only">{{ __('ui.search.natural_label') }}</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        maxlength="300"
                        placeholder="{{ __('Plombier, Électricien, Ménage…') }}"
                        class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest py-3 pl-12 pr-4 font-body-md text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                </div>
            </form>

            <div class="hidden h-20 w-px bg-outline-variant md:block"></div>

            <div class="flex w-full flex-col items-start md:w-auto md:items-center">
                <p class="mb-3 hidden font-body-md text-body-md text-on-surface-variant md:block">{{ __("Besoin d'un nouveau service ?") }}</p>
                <a href="{{ route('services.index') }}"
                   class="flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container md:w-auto">
                    <x-icon name="search" />
                    {{ __("Trouver un prestataire") }}
                </a>
            </div>
        </section>

        <section>
            {{-- Côte à côte comme sur la maquette dès que la place le permet ;
                 en dessous sur un écran de 390 px, où « Vos demandes
                 récentes » à 28 px prend déjà toute la largeur. --}}
            <x-section-header :title="__('Vos demandes récentes')" :href="route('requests.index')" class="mb-6" />

            @if ($requests->isEmpty())
                <x-empty-state
                    :title="__('Vous n\'avez pas encore envoyé de demande.')"
                    :description="__('Trouvez un prestataire près de chez vous et décrivez-lui votre besoin.')"
                    :action-label="__('Explorer les services')"
                    :action-href="route('services.index')"
                />
            @else
                <div class="overflow-hidden rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest">
                    {{-- Les intitulés de colonnes n'apparaissent qu'à partir de
                         `md` : sur un téléphone, chaque champ porte déjà son
                         sens par sa place et sa forme. --}}
                    <div class="hidden grid-cols-12 gap-4 border-b border-outline-variant bg-surface-container-low p-4 font-label-sm text-label-sm uppercase text-on-surface-variant md:grid">
                        <div class="col-span-3">{{ __("Prestataire") }}</div>
                        <div class="col-span-4">{{ __("Service") }}</div>
                        <div class="col-span-2">{{ __("Date") }}</div>
                        <div class="col-span-3 text-right">{{ __("Statut") }}</div>
                    </div>

                    <div class="divide-y divide-outline-variant">
                        @foreach ($requests as $serviceRequest)
                            @php
                                $nom = $serviceRequest->provider?->providerProfile?->business_name
                                    ?? $serviceRequest->provider?->name;
                            @endphp
                            <a href="{{ route('requests.show', $serviceRequest) }}"
                               class="group block p-4 transition-colors hover:bg-surface-container-low md:grid md:grid-cols-12 md:items-center md:gap-4">
                                <div class="mb-2 flex items-center gap-3 md:col-span-3 md:mb-0">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-container-high font-bold text-primary">
                                        {{ $nom ? Str::upper(Str::substr($nom, 0, 2)) : '—' }}
                                    </span>
                                    <span class="min-w-0 font-body-md text-body-md font-semibold text-on-surface group-hover:text-primary">
                                        {{ $nom ?? 'Non assigné' }}
                                    </span>
                                </div>
                                <div class="mb-2 md:col-span-4 md:mb-0">
                                    <span class="font-body-md text-body-md text-on-surface">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</span>
                                </div>
                                <div class="mb-3 md:col-span-2 md:mb-0">
                                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">{{ $serviceRequest->created_at->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="md:col-span-3 md:text-right">
                                    <x-status-badge :status="$serviceRequest->status" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
