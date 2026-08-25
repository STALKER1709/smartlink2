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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-secondary-container/30 border border-outline-variant px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($categories->isEmpty())
            <p class="text-on-surface-variant">Aucune catégorie pour le moment.</p>
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($categories as $category)
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-on-surface">{{ $category->name }}</p>
                            @if ($category->description)
                                <p class="text-sm text-on-surface-variant truncate">{{ $category->description }}</p>
                            @endif
                        </div>

                        <x-status-badge :status="$category->is_active ? 'active' : 'inactive'" />

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Modifier
                            </a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-error hover:opacity-80">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
