@php
    $statusLabels = [
        'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
        'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
        'completed' => 'Terminée', 'cancelled' => 'Annulée',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tableau de bord</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach (['sent', 'accepted', 'in_progress', 'completed'] as $status)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ $counts[$status] ?? 0 }}</p>
                    <p class="text-sm text-gray-500">{{ $statusLabels[$status] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Demandes récentes</h3>
            <a href="{{ route('requests.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Voir tout →</a>
        </div>

        @if ($requests->isEmpty())
            <p class="mt-4 text-gray-500">
                Vous n'avez pas encore envoyé de demande.
                <a href="{{ route('services.index') }}" class="text-indigo-600 hover:text-indigo-800">Explorez les services</a>.
            </p>
        @else
            <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                            </p>
                        </div>
                        <x-status-badge :status="$serviceRequest->status" class="shrink-0" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
