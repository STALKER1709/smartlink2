@php
    $statusLabels = [
        'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
        'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
        'completed' => 'Terminée', 'cancelled' => 'Annulée',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Tableau de bord</h2>
    </x-slot>

    @php
        $statusIcons = ['sent' => 'send', 'accepted' => 'task_alt', 'in_progress' => 'pending_actions', 'completed' => 'check_circle'];
        $statusIconColors = ['sent' => 'text-secondary', 'accepted' => 'text-secondary', 'in_progress' => 'text-secondary', 'completed' => 'text-primary'];
    @endphp

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach (['sent', 'accepted', 'in_progress', 'completed'] as $status)
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-2">
                        <span class="material-symbols-outlined {{ $statusIconColors[$status] }}">{{ $statusIcons[$status] }}</span>
                        <span class="font-label-numeric text-label-numeric text-on-surface-variant">{{ $statusLabels[$status] }}</span>
                    </div>
                    <span class="font-headline-xl text-headline-xl text-on-surface">{{ $counts[$status] ?? 0 }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between">
            <h3 class="font-headline-md text-headline-md text-on-surface">Demandes récentes</h3>
            <a href="{{ route('requests.index') }}" class="text-sm font-semibold text-primary hover:text-primary-container flex items-center gap-1">
                Voir tout
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        @if ($requests->isEmpty())
            <div class="mt-4 bg-surface-container-lowest border border-outline-variant rounded-xl p-12 flex flex-col items-center justify-center text-center">
                <span class="material-symbols-outlined text-5xl text-outline mb-3">inbox</span>
                <p class="text-on-surface-variant">
                    Vous n'avez pas encore envoyé de demande.
                    <a href="{{ route('services.index') }}" class="text-primary hover:text-primary-container font-semibold">Explorez les services</a>.
                </p>
            </div>
        @else
            <div class="mt-4 bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="font-medium text-on-surface truncate">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</p>
                            <p class="text-sm text-on-surface-variant truncate">
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
