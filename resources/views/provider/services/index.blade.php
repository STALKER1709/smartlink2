<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-md text-headline-md text-on-surface">Mes services</h2>
            <a href="{{ route('provider.services.create') }}" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                Publier un service
            </a>
        </div>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($services->isEmpty())
            <p class="text-on-surface-variant">Vous n'avez pas encore publié de service.</p>
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($services as $service)
                    <div class="flex items-center gap-4 p-4">
                        <div class="h-14 w-14 rounded-md bg-surface-container overflow-hidden shrink-0">
                            @if ($service->images->isNotEmpty())
                                <img src="{{ asset('storage/'.$service->images->first()->path) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-on-surface truncate">{{ $service->title }}</p>
                            <p class="text-sm text-on-surface-variant truncate">{{ $service->category?->name }} @if ($service->city) · {{ $service->city }} @endif</p>
                        </div>

                        <x-status-badge :status="$service->status" />
                        @if (! $service->is_available)
                            <x-status-badge status="inactive" />
                        @endif

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('provider.services.edit', $service) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Modifier
                            </a>
                            <form action="{{ route('provider.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Supprimer ce service ?');">
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
