<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gérer les catégories" :subtitle="$categories->total().' '.Str::plural('catégorie', $categories->total())">
            <x-slot name="action">
                <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                    <span class="material-symbols-outlined text-base">add</span>
                    Nouvelle catégorie
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="max-w-4xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-secondary-container/30 border border-outline-variant px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($categories->isEmpty())
            <x-empty-state title="Aucune catégorie pour le moment." description="Les catégories structurent la recherche : sans elles, rien n'est classable." />
        @else
            <x-list-panel>
                @foreach ($categories as $category)
                    <x-list-row class="flex items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-medium text-on-surface">{{ $category->name }}</p>
                                {{-- Seule l'exception se signale : toutes les
                                     catégories sont actives sauf accident. --}}
                                @unless ($category->is_active)
                                    <x-status-badge status="inactive" />
                                @endunless
                            </div>
                            @if ($category->description)
                                {{-- La description était tronquée d'autorité à
                                     une ligne, sur un écran où le nom, la
                                     pastille et deux actions se partageaient
                                     déjà la largeur : il n'en restait que
                                     trois mots. Deux lignes complètes, et rien
                                     d'autre sur la ligne. --}}
                                <p class="mt-0.5 line-clamp-2 text-sm text-on-surface-variant">{{ $category->description }}</p>
                            @endif
                        </div>

                        {{-- « Modifier » et « Supprimer » forment une paire :
                             `ml-auto` les envoyait aux deux bords opposés de
                             l'écran, à trois cents pixels l'une de l'autre. --}}
                        <div class="flex shrink-0 items-center gap-4 pt-0.5">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Modifier
                            </a>
                            {{-- La confirmation nomme la catégorie : « cette
                                 catégorie » ne dit pas laquelle quand on en a
                                 quinze sous les yeux. --}}
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Supprimer « {{ $category->name }} » ? Les services qui s\'y rattachent perdront leur catégorie.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-error hover:opacity-80">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
