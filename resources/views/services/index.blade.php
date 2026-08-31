<x-app-layout :titre="__('seo.services_index')" :description="__('seo.services_index_description')">
    <x-slot name="header">
        <x-page-header heading="h1"
                       :title="__('Catalogue des services')"
                       :subtitle="__('Trouvez des professionnels qualifiés et vérifiés près de chez vous, de la plomberie aux cours particuliers.')" />
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
            'available_only' => request('available_only') ? __('Disponibles uniquement') : null,
        ])->filter();

        $champ = 'w-full rounded-xl border-outline-variant bg-surface font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary';
    @endphp

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-tablet lg:px-margin-desktop">

        {{-- Recherche en langage naturel : l'entrée principale, au-dessus des
             deux colonnes — c'est par elle que passe la majorité des
             visiteurs, les filtres ne servant qu'à corriger. --}}
        <x-natural-search :value="request('q')" />

        {{-- Ce qui a été compris de la phrase, corrigeable par les filtres --}}
        @if (session('searchIntent'))
            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-outline-variant bg-secondary-container/30 px-4 py-3 shadow-elevation-1">
                <span class="font-label-md text-label-md font-semibold text-on-secondary-container">{{ __('ui.search.understood') }}</span>
                @foreach (session('searchIntent') as $piece)
                    <span class="rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 font-label-sm text-label-sm font-semibold text-on-surface">{{ $piece }}</span>
                @endforeach
                <a href="{{ route('services.index') }}" class="ml-auto font-label-sm text-label-sm text-primary underline hover:text-primary-container">
                    {{ __('ui.search.understood_reset') }}
                </a>
            </div>
        @endif

        {{-- La maquette pose le catalogue en deux colonnes : les filtres à
             gauche, collants, et les résultats à droite. La séparation arrive
             à `lg` et non à `md` comme dans la maquette — à 768 px, une
             colonne de 3/12 fait 158 px, où aucun de ces champs ne tient. --}}
        <div class="mt-6 grid grid-cols-12 gap-6">
            <aside class="col-span-12 lg:col-span-4 xl:col-span-3">
                {{-- Replié sur mobile, toujours ouvert dès `lg` : un mur de
                     champs y repousserait les résultats sous la ligne de
                     flottaison. C'est `.filtres` dans `app.css` qui neutralise
                     le repli sur grand écran. --}}
                <details class="filtres rounded-xl border border-outline-variant bg-surface-container-lowest shadow-elevation-1 lg:sticky lg:top-24"
                         @if ($actifs->isNotEmpty() && $services->isNotEmpty()) open @endif>
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 p-4 font-label-md text-label-md font-semibold text-on-surface">
                        <span class="flex items-center gap-2">
                            <x-icon name="tune" size="sm" class="text-on-surface-variant" />
                            {{ __('Filtres') }}
                            @if ($actifs->isNotEmpty())
                                <span class="rounded-full bg-primary px-2 py-0.5 font-label-numeric text-label-sm text-on-primary">{{ $actifs->count() }}</span>
                            @endif
                        </span>
                        <x-icon name="expand_more" size="sm" class="text-on-surface-variant transition-transform group-open:rotate-180" />
                    </summary>

                    <div class="filtres-corps border-t border-outline-variant p-4 lg:border-t-0">
                        <div class="mb-4 hidden items-center justify-between border-b border-outline-variant pb-3 lg:flex">
                            <h2 class="font-headline-md text-headline-md text-on-surface">{{ __('Filtres') }}</h2>
                            <a href="{{ route('services.index') }}" class="-mr-2 inline-flex min-h-11 items-center rounded-lg px-2 font-label-sm text-label-sm text-primary hover:underline">{{ __("Réinitialiser") }}</a>
                        </div>

                        <form id="filtres" action="{{ route('services.index') }}" method="GET" class="space-y-4">
                            <div>
                                <label for="term" class="mb-2 block font-label-md text-label-md text-on-surface">{{ __('Mot-clé') }}</label>
                                <div class="relative">
                                    <x-icon name="search" size="sm" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" />
                                    <input id="term" type="text" name="term" value="{{ request('term') }}"
                                           placeholder="{{ __('Plombier, réparation…') }}"
                                           class="{{ $champ }} pl-10">
                                </div>
                            </div>

                            <div>
                                <label for="category_id" class="mb-2 block font-label-md text-label-md text-on-surface">{{ __('Catégorie') }}</label>
                                <select id="category_id" name="category_id" class="{{ $champ }}">
                                    <option value="">{{ __("Toutes les catégories") }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="city" class="mb-2 block font-label-md text-label-md text-on-surface">{{ __('Ville') }}</label>
                                <select id="city" name="city" class="{{ $champ }}">
                                    <option value="">{{ __("Toutes les villes") }}</option>
                                    @foreach ($villes as $ville)
                                        <option value="{{ $ville }}" @selected(request('city') === $ville)>{{ $ville }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="quarter" class="mb-2 block font-label-md text-label-md text-on-surface">{{ __('Quartier') }}</label>
                                <input id="quarter" type="text" name="quarter" value="{{ request('quarter') }}"
                                       placeholder="{{ __('Akwa, Bastos…') }}" class="{{ $champ }}">
                            </div>

                            <div>
                                <span class="mb-2 block font-label-md text-label-md text-on-surface">{{ __('Disponibilité') }}</span>
                                <label class="flex min-h-11 items-center gap-3 font-body-md text-body-md text-on-surface">
                                    <input type="checkbox" name="available_only" value="1" @checked(request('available_only'))
                                           class="h-5 w-5 rounded border-outline-variant text-primary focus:ring-primary">
                                    {{ __("Disponibles uniquement") }}
                                </label>
                            </div>

                            <x-primary-button class="w-full">
                                {{ __("Appliquer les filtres") }}
                            </x-primary-button>

                            <a href="{{ route('services.index') }}"
                               class="flex min-h-11 items-center justify-center font-label-md text-label-md text-on-surface-variant hover:text-on-surface lg:hidden">
                                {{ __("Réinitialiser") }}
                            </a>
                        </form>
                    </div>
                </details>
            </aside>

            <section class="col-span-12 lg:col-span-8 xl:col-span-9">
                {{-- La barre de résultats : le compte à gauche, le tri à
                     droite. Le tri appartient au formulaire de filtres par son
                     attribut `form`, ce qui lui évite d'être un second
                     formulaire qui perdrait les critères en cours. --}}
                <div class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        <span class="font-label-numeric font-bold text-on-surface">{{ $services->total() }}</span>
                        {{ trans_choice('service trouvé|services trouvés', $services->total()) }}
                    </p>

                    <div class="flex items-center gap-3">
                        <label for="sort" class="whitespace-nowrap font-label-md text-label-md text-on-surface">{{ __('Trier par :') }}</label>
                        <select id="sort" name="sort" form="filtres" onchange="this.form.submit()"
                                class="rounded-xl border-outline-variant bg-surface-container-lowest font-body-md text-body-md text-on-surface shadow-elevation-1 focus:border-primary focus:ring-1 focus:ring-primary">
                            <option value="">{{ __("Plus récents") }}</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __("Prix croissant") }}</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __("Prix décroissant") }}</option>
                        </select>
                    </div>
                </div>

                @if ($actifs->isNotEmpty())
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @foreach ($actifs as $cle => $valeur)
                            <a href="{{ route('services.index', collect(request()->query())->except($cle)->all()) }}"
                               class="flex min-h-11 items-center gap-1 rounded-full border border-outline-variant bg-surface-container-lowest px-3 font-label-sm text-label-sm font-semibold text-on-surface hover:border-primary/50 hover:shadow-elevation-2">
                                {{ $valeur }}
                                <x-icon name="close" size="sm" class="text-on-surface-variant" />
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($services->isEmpty())
                    <x-empty-state class="mt-6"
                        {{-- `q` est la recherche en langage naturel : elle est
                             interprétée puis redirigée vers `term`. À
                             l'affichage de la liste, c'est donc `term` qui
                             porte ce qu'a tapé le visiteur. --}}
                        :title="request('term')
                            ? 'Aucun service ne correspond à « '.request('term').' ».'
                            : 'Aucun service ne correspond à ces filtres.'"
                        :description="__('Essayez avec moins de filtres, une autre ville, ou des mots plus simples — « plombier » plutôt que « réparation de canalisation ».')"
                        :action-label="__('Voir tous les services')"
                        :action-href="route('services.index')"
                    />
                @else
                    <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($services as $service)
                            <x-service-card :service="$service" />
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $services->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
