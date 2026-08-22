<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mes services</h2>
            <a href="{{ route('provider.services.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Publier un service
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($services->isEmpty())
            <p class="text-gray-500">Vous n'avez pas encore publié de service.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($services as $service)
                    <div class="flex items-center gap-4 p-4">
                        <div class="h-14 w-14 rounded-md bg-gray-100 overflow-hidden shrink-0">
                            @if ($service->images->isNotEmpty())
                                <img src="{{ asset('storage/'.$service->images->first()->path) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $service->title }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $service->category?->name }} @if ($service->city) · {{ $service->city }} @endif</p>
                        </div>

                        <x-status-badge :status="$service->status" />
                        @if (! $service->is_available)
                            <x-status-badge status="inactive" class="!bg-amber-100 !text-amber-700" />
                        @endif

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('provider.services.edit', $service) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                Modifier
                            </a>
                            <form action="{{ route('provider.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Supprimer ce service ?');">
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
