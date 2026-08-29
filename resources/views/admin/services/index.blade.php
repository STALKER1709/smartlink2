<x-app-layout :titre="__('Services')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Gérer les services')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-secondary-container/30 border border-outline-variant px-4 py-3 text-label-lg text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" :value="__('Recherche')" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" :placeholder="__('Titre du service')" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Statut')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">{{ __("Tous") }}</option>
                    <option value="{{ \App\Models\Service::STATUS_ACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_ACTIVE)>{{ __("Actif") }}</option>
                    <option value="{{ \App\Models\Service::STATUS_INACTIVE }}" @selected(request('status') === \App\Models\Service::STATUS_INACTIVE)>{{ __("Inactif") }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-full bg-primary px-4 py-2 text-label-lg font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    {{ __("Filtrer") }}
                </button>
            </div>
        </form>

        @if ($services->isEmpty())
            <x-empty-state :title="__('Aucun service trouvé.')" :description="__('Aucun service ne correspond à ces critères.')" />
        @else
            {{-- Le titre d'abord, sur toute la largeur. Il était tronqué à
                 « Recherc… » parce que trois actions se partageaient la ligne :
                 un administrateur ne pouvait pas identifier ce qu'il
                 modérait. Les actions descendent sur leur propre ligne. --}}
            <x-list-panel>
                @foreach ($services as $service)
                    <x-list-row class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-medium text-on-surface">{{ $service->title }}</p>
                                <x-status-badge :status="$service->status" />
                            </div>
                            <p class="mt-0.5 text-label-lg text-on-surface-variant">
                                {{ $service->provider?->name }} · {{ $service->category?->name }}
                                @if ($service->city) · {{ $service->city }} @endif
                            </p>
                        </div>

                        {{-- « Supprimer » est irréversible et se trouvait collé à
                             « Désactiver », deux cibles voisines au pouce. Il
                             est mis à part, à l'autre bout de la rangée. --}}
                        <div class="flex shrink-0 items-center gap-4">
                            <a href="{{ route('services.show', $service) }}" class="text-label-lg font-medium text-primary hover:text-primary-container">
                                {{ __("Voir") }}
                            </a>
                            <form action="{{ route('admin.services.toggle-status', $service) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-label-lg font-medium text-tertiary hover:opacity-80">
                                    {{ $service->status === \App\Models\Service::STATUS_ACTIVE ? 'Désactiver' : 'Activer' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="ml-auto sm:ml-4" onsubmit="return confirm('Supprimer définitivement « {{ $service->title }} » ? Cette action est irréversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-label-lg font-medium text-error hover:opacity-80">
                                    {{ __("Supprimer") }}
                                </button>
                            </form>
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
