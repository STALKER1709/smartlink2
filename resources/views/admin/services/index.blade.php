<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gérer les services</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" value="Recherche" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" placeholder="Titre du service" />
            </div>
            <div>
                <x-input-label for="status" value="Statut" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\Service::STATUS_ACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_ACTIVE)>Actif</option>
                    <option value="{{ \App\Models\Service::STATUS_INACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_INACTIVE)>Inactif</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($services->isEmpty())
            <p class="text-gray-500">Aucun service trouvé.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($services as $service)
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $service->title }}</p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $service->provider?->name }} · {{ $service->category?->name }}
                                @if ($service->city) · {{ $service->city }} @endif
                            </p>
                        </div>

                        <x-status-badge :status="$service->status" />

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('services.show', $service) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Voir
                            </a>
                            <form action="{{ route('admin.services.toggle-status', $service) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm font-medium text-amber-600 hover:text-amber-800">
                                    {{ $service->status === \App\Models\Service::STATUS_ACTIVE ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce service ?');">
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
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
