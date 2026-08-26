<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mes services" :subtitle="$services->total().' '.Str::plural('service', $services->total()).' publié'.($services->total() > 1 ? 's' : '')">
            <x-slot name="action">
                <a href="{{ route('provider.services.create') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                    <span class="material-symbols-outlined text-base">add</span>
                    Publier un service
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if ($services->isEmpty())
            <x-empty-state
                title="Vous n'avez pas encore publié de service."
                description="Un service bien décrit, avec une photo et un prix indicatif, est ce qui décide un client à vous écrire."
                action-label="Publier mon premier service"
                :action-href="route('provider.services.create')"
            />
        @else
            <div class="divide-y divide-outline-variant overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                @foreach ($services as $service)
                    <div class="flex flex-wrap items-center gap-4 p-4">
                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-secondary-container/40">
                            @if ($service->images->isNotEmpty())
                                <img src="{{ media_url($service->images->first()->path) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <x-category-icon :icon="$service->category?->icon" class="text-xl text-primary/40" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <a href="{{ route('services.show', $service) }}" class="block truncate font-medium text-on-surface hover:text-primary">
                                {{ $service->title }}
                            </a>
                            <p class="truncate text-sm text-on-surface-variant">
                                {{ $service->category?->name }}@if ($service->city) · {{ $service->city }} @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <x-status-badge :status="$service->status" />
                            @if (! $service->is_available)
                                <x-status-badge status="inactive" />
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route('provider.services.edit', $service) }}"
                               class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium text-primary transition-colors hover:bg-primary-container/15"
                               title="Modifier">
                                <span class="material-symbols-outlined text-base">edit</span>
                                <span class="hidden sm:inline">Modifier</span>
                            </a>

                            <form action="{{ route('provider.services.destroy', $service) }}" method="POST"
                                  onsubmit="return confirm('Supprimer « {{ $service->title }} » ? Cette action est définitive.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-medium text-error transition-colors hover:bg-error-container/30"
                                        title="Supprimer">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                    <span class="hidden sm:inline">Supprimer</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
