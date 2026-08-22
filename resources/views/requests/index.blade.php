<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Demandes</h2>
            @if (Auth::user()->isClient())
                <a href="{{ route('requests.create') }}" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    Nouvelle demande
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @php
            $statuses = [
                'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
                'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
                'completed' => 'Terminée', 'cancelled' => 'Annulée',
            ];
        @endphp

        <div class="flex flex-wrap gap-2 mb-6">
            <a
                href="{{ route('requests.index') }}"
                class="rounded-full px-3 py-1 text-xs font-medium {{ request('status') ? 'bg-gray-100 text-gray-600' : 'bg-brand-600 text-white' }}"
            >
                Toutes
            </a>
            @foreach ($statuses as $value => $label)
                <a
                    href="{{ route('requests.index', ['status' => $value]) }}"
                    class="rounded-full px-3 py-1 text-xs font-medium {{ request('status') === $value ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($requests->isEmpty())
            <p class="text-gray-500">Aucune demande pour le moment.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-gray-50">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">
                                {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                @if (Auth::user()->isProvider())
                                    {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                                @else
                                    {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                                @endif
                                · {{ $serviceRequest->created_at->format('d/m/Y') }}
                            </p>
                        </div>
                        <x-status-badge :status="$serviceRequest->status" class="shrink-0" />
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
