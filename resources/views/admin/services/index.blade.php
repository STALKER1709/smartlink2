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
            <x-empty-state icon="home_repair_service" title="Aucun service trouvé." description="Aucun service ne correspond à ces critères." />
        @else
            {{-- Le titre d'abord, sur toute la largeur. Il était tronqué à
                 « Recherc… » parce que trois actions se partageaient la ligne :
                 un administrateur ne pouvait pas identifier ce qu'il
                 modérait. Les actions descendent sur leur propre ligne. --}}
            <div class="-mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6">
                @foreach ($services as $service)
                    <div class="flex flex-col gap-3 border-b border-outline-variant py-4 sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-medium text-on-surface">{{ $service->title }}</p>
                                <x-status-badge :status="$service->status" />
                            </div>
                            <p class="mt-0.5 text-sm text-on-surface-variant">
                                {{ $service->provider?->name }} · {{ $service->category?->name }}
                                @if ($service->city) · {{ $service->city }} @endif
                            </p>
                        </div>

                        {{-- « Supprimer » est irréversible et se trouvait collé à
                             « Désactiver », deux cibles voisines au pouce. Il
                             est mis à part, à l'autre bout de la rangée. --}}
                        <div class="flex shrink-0 items-center gap-4">
                            <a href="{{ route('services.show', $service) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Voir
                            </a>
                            <form action="{{ route('admin.services.toggle-status', $service) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-sm font-medium text-tertiary hover:opacity-80">
                                    {{ $service->status === \App\Models\Service::STATUS_ACTIVE ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="ml-auto sm:ml-4" onsubmit="return confirm('Supprimer définitivement « {{ $service->title }} » ? Cette action est irréversible.');">
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
