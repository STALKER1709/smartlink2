<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gérer les catégories</h2>
            <a href="{{ route('admin.categories.create') }}" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                Nouvelle catégorie
            </a>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($categories->isEmpty())
            <p class="text-gray-500">Aucune catégorie pour le moment.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($categories as $category)
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900">{{ $category->name }}</p>
                            @if ($category->description)
                                <p class="text-sm text-gray-500 truncate">{{ $category->description }}</p>
                            @endif
                        </div>

                        <x-status-badge :status="$category->is_active ? 'active' : 'inactive'" />

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-brand-600 hover:text-brand-800">
                                Modifier
                            </a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
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
