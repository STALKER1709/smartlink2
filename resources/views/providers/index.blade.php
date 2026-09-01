<x-app-layout :titre="__('seo.providers_index')" :description="__('seo.providers_index_description')">
    <x-slot name="header">
        <x-page-header heading="h1"
                       :title="__('Annuaire des prestataires')"
                       :subtitle="__('Des professionnels qualifiés pour vos besoins immédiats ou vos projets de longue haleine.')" />
    </x-slot>

    @php
        $actifs = collect([
            'term' => request('term'),
            'category_id' => $categories->firstWhere('id', request('category_id'))?->name,
            'city' => request('city'),
            'verified_only' => request('verified_only') ? __('Vérifiés uniquement') : null,
        ])->filter();
    @endphp

    <div class="mx-auto max-w-container space-y-5 px-margin-mobile py-8 md:px-margin-tablet lg:px-margin-desktop">

        <form action="{{ route('providers.index') }}" method="GET">
            <div class="flex flex-col gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2 shadow-elevation-1 sm:flex-row sm:items-center">
                <label for="term" class="sr-only">{{ __("Rechercher un prestataire") }}</label>
                <div class="flex flex-1 items-center gap-2 px-3">
                    <x-icon name="search" class="text-on-surface-variant" />
                    <input
                        type="text"
                        id="term"
                        name="term"
                        value="{{ request('term') }}"
                        placeholder="{{ __('Nom d\'entreprise, métier…') }}"
                        class="w-full border-0 bg-transparent py-2.5 text-on-surface placeholder:text-on-surface-variant/70 focus:ring-0"
                    >
                </div>

                <select name="category_id" class="min-h-11 rounded-xl border-outline-variant text-label-md focus:border-primary focus:ring-primary sm:w-48">
                    <option value="">{{ __("Toutes les catégories") }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <input
                    type="text"
                    name="city"
                    value="{{ request('city') }}"
                    placeholder="{{ __('Ville') }}"
                    class="min-h-11 rounded-xl border-outline-variant text-label-md focus:border-primary focus:ring-primary sm:w-40"
                >

                <x-primary-button class="shrink-0">
                    {{ __("Chercher") }}
                </x-primary-button>
            </div>

            {{-- Le panneau à bascule de la maquette : la vérification est le
                 premier critère de confiance de l'annuaire, et une case à
                 cocher perdue sous la barre de recherche ne le disait pas. --}}
            <div class="mt-4 flex flex-col items-start justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-low p-5 sm:flex-row sm:items-center">
                <div>
                    <p class="flex items-center gap-2 font-label-md text-body-md font-bold text-on-surface">
                        {{ __("Vérifiés uniquement") }}
                        <x-icon name="verified" size="sm" class="text-primary" />
                    </p>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                        {{ __("N'afficher que les profils dont la pièce d'identité a été contrôlée.") }}
                    </p>
                </div>

                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" name="verified_only" value="1" @checked(request('verified_only'))
                           onchange="this.form.submit()"
                           aria-label="{{ __('Vérifiés uniquement') }}"
                           class="peer sr-only">
                    <span class="block h-8 w-14 rounded-full bg-surface-container-high transition-colors peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary"></span>
                    <span class="absolute left-1 h-6 w-6 rounded-full bg-surface-container-lowest shadow-elevation-1 transition-transform peer-checked:translate-x-6"></span>
                </label>
            </div>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-body-md text-body-md text-on-surface-variant">
                <span class="font-label-numeric font-bold text-on-surface">{{ $providers->total() }}</span>
                {{ trans_choice('prestataire trouvé|prestataires trouvés', $providers->total()) }}
            </p>

            @if ($actifs->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($actifs as $cle => $valeur)
                        <a href="{{ route('providers.index', collect(request()->query())->except($cle)->all()) }}"
                           class="flex items-center gap-1 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-label-sm font-medium text-on-surface hover:border-primary/50 hover:shadow-elevation-2">
                            {{ $valeur }}
                            <x-icon name="close" size="sm" class="text-on-surface-variant" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($providers->isEmpty())
            <x-empty-state
                :title="request('term')
                    ? 'Aucun prestataire ne correspond à « '.request('term').' ».'
                    : 'Aucun prestataire ne correspond à cette recherche.'"
                :description="__('Essayez une autre ville, élargissez la catégorie, ou décochez « vérifiés uniquement ».')"
                :action-label="__('Voir tout l\'annuaire')"
                :action-href="route('providers.index')"
            />
        @else
            {{-- Empilées sur toute la largeur, comme dans la maquette : la
                 zone d'intervention et la note ne tiennent pas dans une carte
                 de grille. --}}
            <div class="flex flex-col gap-4">
                @foreach ($providers as $providerProfile)
                    <x-provider-card :provider-profile="$providerProfile" />
                @endforeach
            </div>

            <div class="pt-2">{{ $providers->links() }}</div>
        @endif
    </div>
</x-app-layout>
