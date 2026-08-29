<x-app-layout :titre="__('seo.services_index')" :description="__('seo.services_index_description')">
    <x-slot name="header">
        <x-page-header :title="__('Services')" />
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
        <x-natural-search :value="request('q')" />

        {{-- Ce qui a été compris de la phrase, corrigeable par les filtres ci-dessous --}}
        @if (session('searchIntent'))
            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-outline-variant shadow-elevation-1 bg-secondary-container/30 px-4 py-3">
                <span class="text-label-md font-medium text-on-secondary-container">{{ __('ui.search.understood') }}</span>
                @foreach (session('searchIntent') as $piece)
                    <span class="rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-label-sm font-medium text-on-surface">{{ $piece }}</span>
                @endforeach
                <a href="{{ route('services.index') }}" class="ml-auto text-label-sm text-primary underline hover:text-primary-container">
                    {{ __('ui.search.understood_reset') }}
                </a>
            </div>
        @endif

        {{-- Filtres classiques, repliés par défaut : sur mobile, un mur de champs
             repoussait les résultats sous la ligne de flottaison. --}}
        {{-- Le panneau s'ouvre quand des filtres sont actifs, SAUF si la
             recherche ne rend rien : sur un téléphone il occupe alors tout
             l'écran et le « aucun résultat » passe dessous. Le visiteur voit un
             formulaire au lieu d'une réponse, et ne sait pas si sa recherche a
             abouti. --}}
        <details class="group rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest"
                 @if ($actifs->isNotEmpty() && $services->isNotEmpty()) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 font-semibold text-on-surface">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant">tune</span>
                    {{ __('ui.search.or_filter') }}
                    @if ($actifs->isNotEmpty())
                        <span class="rounded-full bg-primary px-2 py-0.5 font-label-numeric text-label-sm text-on-primary">{{ $actifs->count() }}</span>
                    @endif
                </span>
                <span class="material-symbols-outlined text-on-surface-variant transition-transform group-open:rotate-180">expand_more</span>
            </summary>

            <form action="{{ route('services.index') }}" method="GET" class="grid grid-cols-1 gap-3 border-t border-outline-variant p-4 sm:grid-cols-2 lg:grid-cols-3">
                <input type="text" name="term" value="{{ request('term') }}" placeholder="{{ __('Mot-clé') }}"
                       class="rounded-lg border-outline-variant text-label-md focus:border-primary focus:ring-primary">

                <select name="category_id" class="rounded-lg border-outline-variant text-label-md focus:border-primary focus:ring-primary">
                    <option value="">{{ __("Toutes les catégories") }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <select name="city" class="rounded-lg border-outline-variant text-label-md focus:border-primary focus:ring-primary">
                    <option value="">{{ __("Toutes les villes") }}</option>
                    @foreach ($villes as $ville)
                        <option value="{{ $ville }}" @selected(request('city') === $ville)>{{ $ville }}</option>
                    @endforeach
                </select>

                <input type="text" name="quarter" value="{{ request('quarter') }}" placeholder="{{ __('Quartier') }}"
                       class="rounded-lg border-outline-variant text-label-md focus:border-primary focus:ring-primary">

                <select name="sort" class="rounded-lg border-outline-variant text-label-md focus:border-primary focus:ring-primary">
                    <option value="">{{ __("Plus récents") }}</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __("Prix croissant") }}</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __("Prix décroissant") }}</option>
                </select>

                <label class="flex items-center gap-2 text-label-md text-on-surface-variant">
                    <input type="checkbox" name="available_only" value="1" @checked(request('available_only'))
                           class="rounded border-outline-variant text-primary focus:ring-primary">
                    {{ __("Disponibles uniquement") }}
                </label>

                {{-- La bulle de l'assistant flotte au-dessus du contenu et se
                     posait sur ce bouton. Pleine largeur sur mobile, il reste
                     atteignable au pouce et son libellé se lit à gauche d'elle. --}}
                <div class="flex flex-col-reverse items-stretch gap-2 sm:col-span-2 sm:flex-row sm:justify-end lg:col-span-3">
                    <a href="{{ route('services.index') }}" class="px-3 py-2 text-center text-label-md text-on-surface-variant hover:text-on-surface">{{ __("Réinitialiser") }}</a>
                    <button type="submit" class="rounded-full bg-primary px-5 py-3 font-button-text text-label-md font-semibold text-on-primary transition-colors hover:bg-primary-container sm:py-2">
                        {{ __("Filtrer") }}
                    </button>
                </div>
            </form>
        </details>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-label-md text-label-md text-on-surface-variant">
                <span class="font-label-numeric">{{ $services->total() }}</span> {{ Str::plural('service', $services->total()) }}
            </p>

            @if ($actifs->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($actifs as $cle => $valeur)
                        <a href="{{ route('services.index', collect(request()->query())->except($cle)->all()) }}"
                           class="flex items-center gap-1 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-label-sm font-medium text-on-surface hover:border-primary/50 hover:shadow-elevation-2">
                            {{ $valeur }}
                            <span class="material-symbols-outlined text-sm text-on-surface-variant">close</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($services->isEmpty())
            <x-empty-state
                {{-- `q` est la recherche en langage naturel : elle est
                     interprétée puis redirigée vers `term`. À l'affichage de la
                     liste, c'est donc `term` qui porte ce qu'a tapé le
                     visiteur. --}}
                :title="request('term')
                    ? 'Aucun service ne correspond à « '.request('term').' ».'
                    : 'Aucun service ne correspond à ces filtres.'"
                :description="__('Essayez avec moins de filtres, une autre ville, ou des mots plus simples — « plombier » plutôt que « réparation de canalisation ».')"
                :action-label="__('Voir tous les services')"
                :action-href="route('services.index')"
            />
        @else
            <div class="-mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6 lg:grid lg:grid-cols-2 lg:gap-x-10">
                @foreach ($services as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>

            <div>{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
