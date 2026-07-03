<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Services</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                @foreach (['Yaoundé','Douala','Bafoussam','Bamenda','Garoua','Maroua','Ngaoundéré','Bertoua','Kribi','Limbé','Buea','Ebolowa','Kumba','Nkongsamba','Edéa','Bafia'] as $ville)
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
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
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
