<x-app-layout :titre="__('seo.providers_index')" :description="__('seo.providers_index_description')">
    <x-slot name="header">
        <x-page-header :title="__('Annuaire des prestataires')"
                       :subtitle="__('Trouvez les meilleurs professionnels au Cameroun.')" />
    </x-slot>

    @php
        $actifs = collect([
            'term' => request('term'),
            'category_id' => $categories->firstWhere('id', request('category_id'))?->name,
            'city' => request('city'),
            'verified_only' => request('verified_only') ? __('Vérifiés uniquement') : null,
        ])->filter();
    @endphp

    <div class="mx-auto max-w-container space-y-5 px-margin-mobile py-8 md:px-margin-desktop">

        <form action="{{ route('providers.index') }}" method="GET">
            <div class="flex flex-col gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2 shadow-sm sm:flex-row sm:items-center">
                <label for="term" class="sr-only">{{ __("Rechercher un prestataire") }}</label>
                <div class="flex flex-1 items-center gap-2 px-3">
                    <span class="material-symbols-outlined text-on-surface-variant">search</span>
                    <input
                        type="text"
                        id="term"
                        name="term"
                        value="{{ request('term') }}"
                        placeholder="{{ __('Nom d\'entreprise, métier…') }}"
                        class="w-full border-0 bg-transparent py-2.5 text-on-surface placeholder:text-on-surface-variant/70 focus:ring-0"
                    >
                </div>

                <select name="category_id" class="rounded-xl border-outline-variant text-label-lg focus:border-primary focus:ring-primary sm:w-48">
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
                    class="rounded-xl border-outline-variant text-label-lg focus:border-primary focus:ring-primary sm:w-40"
                >

                <button type="submit" class="shrink-0 rounded-xl bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                    {{ __("Chercher") }}
                </button>
            </div>

            <label class="mt-3 inline-flex items-center gap-2 px-1 text-label-lg text-on-surface-variant">
                <input type="checkbox" name="verified_only" value="1" @checked(request('verified_only'))
                       onchange="this.form.submit()"
                       class="rounded border-outline-variant text-primary focus:ring-primary">
                {{ __("Prestataires vérifiés uniquement") }}
            </label>
        </form>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="font-label-lg text-label-lg text-on-surface-variant">
                <span class="font-label-numeric">{{ $providers->total() }}</span> {{ Str::plural('prestataire', $providers->total()) }}
            </p>

            @if ($actifs->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($actifs as $cle => $valeur)
                        <a href="{{ route('providers.index', collect(request()->query())->except($cle)->all()) }}"
                           class="flex items-center gap-1 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1 text-label-md font-medium text-on-surface hover:border-primary/50">
                            {{ $valeur }}
                            <span class="material-symbols-outlined text-sm text-on-surface-variant">close</span>
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
            <div class="-mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6 lg:grid lg:grid-cols-2 lg:gap-x-10">
                @foreach ($providers as $providerProfile)
                    <x-provider-card :provider-profile="$providerProfile" />
                @endforeach
            </div>

            <div>{{ $providers->links() }}</div>
        @endif
    </div>
</x-app-layout>
