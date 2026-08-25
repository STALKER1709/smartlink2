<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Services</h2>
    </x-slot>

    @php
        $villes = ['Yaoundé','Douala','Bafoussam','Bamenda','Garoua','Maroua','Ngaoundéré','Bertoua','Kribi','Limbé','Buea','Ebolowa','Kumba','Nkongsamba','Edéa','Bafia'];
        // Une ville reconnue hors de cette liste doit rester sélectionnée.
        if (request('city') && ! in_array(request('city'), $villes, true)) {
            array_unshift($villes, request('city'));
        }

        // Les filtres actifs, résumés en pastilles retirables : sans ça, on ne
        // sait plus pourquoi la liste est courte.
        $actifs = collect([
            'term' => request('term'),
            'category_id' => $categories->firstWhere('id', request('category_id'))?->name,
            'city' => request('city'),
            'quarter' => request('quarter'),
            'available_only' => request('available_only') ? 'Disponibles uniquement' : null,
        ])->filter();
    @endphp

    <div class="mx-auto max-w-container space-y-5 px-margin-mobile py-8 md:px-margin-desktop">

        {{-- Recherche en langage naturel : l'entrée principale --}}
        <form action="{{ route('services.index') }}" method="GET">
            <div class="flex flex-col gap-2 rounded-2xl border border-outline-variant bg-surface-container-lowest p-2 shadow-sm sm:flex-row sm:items-center">
                <label for="q" class="sr-only">{{ __('ui.search.natural_label') }}</label>
                <div class="flex flex-1 items-center gap-2 px-3">
                    <span class="material-symbols-outlined text-on-surface-variant">search</span>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        maxlength="300"
                        placeholder="{{ __('ui.search.natural_placeholder') }}"
                        class="w-full border-0 bg-transparent py-2.5 text-on-surface placeholder:text-on-surface-variant/70 focus:ring-0"
                    >
                </div>
                <button type="submit" class="shrink-0 rounded-xl bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                    {{ __('ui.search.natural_submit') }}
                </button>
            </div>
            <p class="mt-2 px-1 text-xs text-on-surface-variant">{{ __('ui.search.natural_hint') }}</p>
        </form>

        {{-- Ce qui a été compris de la phrase, corrigeable par les filtres ci-dessous --}}
        @if (session('searchIntent'))
            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-outline-variant bg-secondary-container/30 px-4 py-3">
                <span class="text-sm font-medium text-on-secondary-container">{{ __('ui.search.understood') }}</span>
                @foreach (session('searchIntent') as $piece)
                    <span class="rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-xs font-medium text-on-surface">{{ $piece }}</span>
                @endforeach
                <a href="{{ route('services.index') }}" class="ml-auto text-xs text-primary underline hover:text-primary-container">
                    {{ __('ui.search.understood_reset') }}
                </a>
            </div>
        @endif

        {{-- Filtres classiques, repliés par défaut : sur mobile, un mur de champs
             repoussait les résultats sous la ligne de flottaison. --}}
        <details class="group rounded-xl border border-outline-variant bg-surface-container-lowest" @if ($actifs->isNotEmpty()) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 font-semibold text-on-surface">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant">tune</span>
                    {{ __('ui.search.or_filter') }}
                    @if ($actifs->isNotEmpty())
                        <span class="rounded-full bg-primary px-2 py-0.5 font-label-numeric text-xs text-on-primary">{{ $actifs->count() }}</span>
                    @endif
                </span>
                <span class="material-symbols-outlined text-on-surface-variant transition-transform group-open:rotate-180">expand_more</span>
            </summary>

            <form action="{{ route('services.index') }}" method="GET" class="grid grid-cols-1 gap-3 border-t border-outline-variant p-4 sm:grid-cols-2 lg:grid-cols-3">
                <input type="text" name="term" value="{{ request('term') }}" placeholder="Mot-clé"
                       class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">

                <select name="category_id" class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <select name="city" class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                    <option value="">Toutes les villes</option>
                    @foreach ($villes as $ville)
                        <option value="{{ $ville }}" @selected(request('city') === $ville)>{{ $ville }}</option>
                    @endforeach
                </select>

                <input type="text" name="quarter" value="{{ request('quarter') }}" placeholder="Quartier"
                       class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">

                <select name="sort" class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                    <option value="">Plus récents</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Prix croissant</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Prix décroissant</option>
                </select>

                <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                    <input type="checkbox" name="available_only" value="1" @checked(request('available_only'))
                           class="rounded border-outline-variant text-primary focus:ring-primary">
                    Disponibles uniquement
                </label>

                <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-3">
                    <a href="{{ route('services.index') }}" class="px-3 py-2 text-sm text-on-surface-variant hover:text-on-surface">Réinitialiser</a>
                    <button type="submit" class="rounded-full bg-primary px-5 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                        Filtrer
                    </button>
                </div>
            </form>
        </details>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-label-numeric text-label-numeric text-on-surface-variant">
                {{ $services->total() }} {{ Str::plural('service', $services->total()) }}
            </p>

            @if ($actifs->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($actifs as $cle => $valeur)
                        <a href="{{ route('services.index', collect(request()->query())->except($cle)->all()) }}"
                           class="flex items-center gap-1 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-xs font-medium text-on-surface hover:border-primary/50">
                            {{ $valeur }}
                            <span class="material-symbols-outlined text-sm text-on-surface-variant">close</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($services->isEmpty())
            <div class="rounded-xl border border-dashed border-outline-variant p-12 text-center">
                <span class="material-symbols-outlined text-4xl text-on-surface-variant/50">search_off</span>
                <p class="mt-3 font-headline-md text-base font-semibold text-on-surface">Aucun service ne correspond à votre recherche.</p>
                <p class="mt-1 text-sm text-on-surface-variant">Essayez avec moins de filtres, ou une autre ville.</p>
                <a href="{{ route('services.index') }}" class="mt-5 inline-flex rounded-full bg-primary px-5 py-2.5 font-button-text text-sm font-semibold text-on-primary hover:bg-primary-container">
                    Voir tous les services
                </a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>

            <div>{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
