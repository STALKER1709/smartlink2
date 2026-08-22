<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tableau de bord</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $servicesCount }}</p>
                <p class="text-sm text-gray-500">Services publiés</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $activeServicesCount }}</p>
                <p class="text-sm text-gray-500">Services actifs</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $pendingCount }}</p>
                <p class="text-sm text-gray-500">Demandes en attente</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $counts['completed'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Prestations terminées</p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold text-gray-900">Demandes récentes</h3>
            <div class="flex gap-3">
                <a href="{{ route('provider.services.create') }}" class="text-sm font-medium text-white bg-brand-600 rounded-md px-3 py-1.5 hover:bg-brand-700">
                    Publier un service
                </a>
                <a href="{{ route('requests.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-800">Voir tout →</a>
            </div>
        </div>

        @if ($requests->isEmpty())
            <p class="mt-4 text-gray-500">Vous n'avez pas encore reçu de demande.</p>
        @else
            <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                            </p>
                        </div>
                        <x-status-badge :status="$serviceRequest->status" class="shrink-0" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
