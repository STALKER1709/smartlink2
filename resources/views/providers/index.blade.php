<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prestataires</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="{{ route('providers.index') }}" method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input
                type="text"
                name="term"
                value="{{ request('term') }}"
                placeholder="Rechercher un prestataire…"
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

            <input
                type="text"
                name="city"
                value="{{ request('city') }}"
                placeholder="Ville"
                class="rounded-md border-gray-300 text-sm"
            >

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="verified_only" value="1" @checked(request('verified_only')) class="rounded border-gray-300">
                Vérifiés uniquement
            </label>

            <div class="lg:col-span-3 flex justify-end gap-2">
                <a href="{{ route('providers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Réinitialiser</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($providers->isEmpty())
            <p class="mt-8 text-gray-500">Aucun prestataire ne correspond à votre recherche.</p>
        @else
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($providers as $providerProfile)
                    <x-provider-card :provider-profile="$providerProfile" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $providers->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
