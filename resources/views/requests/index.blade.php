<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-md text-headline-md text-on-surface">Demandes</h2>
            @if (Auth::user()->isClient())
                <a href="{{ route('requests.create') }}" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    Nouvelle demande
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
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
                class="rounded-full px-3 py-1 text-xs font-semibold {{ request('status') ? 'bg-surface-container-high text-on-surface-variant' : 'bg-primary text-on-primary' }}"
            >
                Toutes
            </a>
            @foreach ($statuses as $value => $label)
                <a
                    href="{{ route('requests.index', ['status' => $value]) }}"
                    class="rounded-full px-3 py-1 text-xs font-semibold {{ request('status') === $value ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($requests->isEmpty())
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-12 flex flex-col items-center justify-center text-center">
                <span class="material-symbols-outlined text-5xl text-outline mb-3">inbox</span>
                <p class="text-on-surface-variant">Aucune demande pour le moment.</p>
            </div>
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="font-medium text-on-surface truncate">
                                {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                            </p>
                            <p class="text-sm text-on-surface-variant truncate">
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
