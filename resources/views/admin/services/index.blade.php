<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gérer les services" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-secondary-container/30 border border-outline-variant px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" value="Recherche" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" placeholder="Titre du service" />
            </div>
            <div>
                <x-input-label for="status" value="Statut" />
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\Service::STATUS_ACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_ACTIVE)>Actif</option>
                    <option value="{{ \App\Models\Service::STATUS_INACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_INACTIVE)>Inactif</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($services->isEmpty())
            <p class="text-on-surface-variant">Aucun service trouvé.</p>
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($services as $service)
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-on-surface truncate">{{ $service->title }}</p>
                            <p class="text-sm text-on-surface-variant truncate">
                                {{ $service->provider?->name }} · {{ $service->category?->name }}
                                @if ($service->city) · {{ $service->city }} @endif
                            </p>
                        </div>

                        <x-status-badge :status="$service->status" />

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('services.show', $service) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Voir
                            </a>
                            <form action="{{ route('admin.services.toggle-status', $service) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm font-medium text-tertiary hover:opacity-80">
                                    {{ $service->status === \App\Models\Service::STATUS_ACTIVE ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce service ?');">
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
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
