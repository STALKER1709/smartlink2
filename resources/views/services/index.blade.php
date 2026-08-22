<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Services</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-4">

        {{-- Recherche en langage naturel --}}
        <form action="{{ route('services.index') }}" method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <label for="q" class="block text-sm font-medium text-gray-700">{{ __('ui.search.natural_label') }}</label>
            <div class="mt-2 flex flex-col sm:flex-row gap-2">
                <input
                    type="text"
                    id="q"
                    name="q"
                    maxlength="300"
                    placeholder="{{ __('ui.search.natural_placeholder') }}"
                    class="flex-1 rounded-md border-gray-300 text-sm"
                >
                <button type="submit" class="rounded-md bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    {{ __('ui.search.natural_submit') }}
                </button>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ __('ui.search.natural_hint') }}</p>
        </form>

        {{-- Ce qui a été compris de la phrase, corrigeable par les filtres ci-dessous --}}
        @if (session('searchIntent'))
            <div class="rounded-md bg-brand-50 border border-brand-200 px-4 py-3 flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-brand-900">{{ __('ui.search.understood') }}</span>
                @foreach (session('searchIntent') as $piece)
                    <span class="rounded-full bg-white border border-brand-200 px-3 py-1 text-xs font-medium text-brand-800">{{ $piece }}</span>
                @endforeach
                <a href="{{ route('services.index') }}" class="ml-auto text-xs text-brand-700 hover:text-brand-900 underline">
                    {{ __('ui.search.understood_reset') }}
                </a>
            </div>
        @endif

        <p class="text-xs uppercase tracking-wide text-gray-400">{{ __('ui.search.or_filter') }}</p>

        <form action="{{ route('services.index') }}" method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <input
                type="text"
                name="term"
                value="{{ request('term') }}"
                placeholder="Rechercher un service…"
                class="rounded-md border-gray-300 text-sm lg:col-span-2"
            >

            <select name="category_id" class="rounded-md border-gray-300 text-sm">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select name="city" class="rounded-md border-gray-300 text-sm">
                <option value="">Toutes les villes</option>
                @php
                    $villes = ['Yaoundé','Douala','Bafoussam','Bamenda','Garoua','Maroua','Ngaoundéré','Bertoua','Kribi','Limbé','Buea','Ebolowa','Kumba','Nkongsamba','Edéa','Bafia'];
                    // Une ville reconnue hors de cette liste doit rester sélectionnée.
                    if (request('city') && ! in_array(request('city'), $villes, true)) {
                        array_unshift($villes, request('city'));
                    }
                @endphp
                @foreach ($villes as $ville)
                    <option value="{{ $ville }}" @selected(request('city') === $ville)>{{ $ville }}</option>
                @endforeach
            </select>

            <input
                type="text"
                name="quarter"
                value="{{ request('quarter') }}"
                placeholder="Quartier"
                class="rounded-md border-gray-300 text-sm"
            >

            <select name="sort" class="rounded-md border-gray-300 text-sm">
                <option value="">Plus récents</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Prix croissant</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Prix décroissant</option>
            </select>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="available_only" value="1" @checked(request('available_only')) class="rounded border-gray-300">
                Disponibles uniquement
            </label>

            <div class="lg:col-span-5 flex justify-end gap-2">
                <a href="{{ route('services.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Réinitialiser</a>
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($services->isEmpty())
            <p class="mt-8 text-gray-500">Aucun service ne correspond à votre recherche.</p>
        @else
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($services as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
