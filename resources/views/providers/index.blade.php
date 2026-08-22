<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Prestataires</h2>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <form action="{{ route('providers.index') }}" method="GET" class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input
                type="text"
                name="term"
                value="{{ request('term') }}"
                placeholder="Rechercher un prestataire…"
                class="rounded-lg border-outline-variant text-sm lg:col-span-2 focus:border-primary focus:ring-primary"
            >

            <select name="category_id" class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
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
                class="rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary"
            >

            <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                <input type="checkbox" name="verified_only" value="1" @checked(request('verified_only')) class="rounded border-outline-variant text-primary focus:ring-primary">
                Vérifiés uniquement
            </label>

            <div class="lg:col-span-3 flex justify-end gap-2">
                <a href="{{ route('providers.index') }}" class="text-sm text-on-surface-variant hover:text-on-surface px-3 py-2">Réinitialiser</a>
                <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($providers->isEmpty())
            <p class="mt-8 text-on-surface-variant">Aucun prestataire ne correspond à votre recherche.</p>
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
