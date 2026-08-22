<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Tableau de bord</h2>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <span class="material-symbols-outlined text-secondary">home_repair_service</span>
                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">Services publiés</span>
                </div>
                <span class="font-headline-xl text-headline-xl text-on-surface">{{ $servicesCount }}</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <span class="material-symbols-outlined text-primary">check_circle</span>
                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">Services actifs</span>
                </div>
                <span class="font-headline-xl text-headline-xl text-on-surface">{{ $activeServicesCount }}</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <span class="material-symbols-outlined text-secondary">pending_actions</span>
                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">Demandes en attente</span>
                </div>
                <span class="font-headline-xl text-headline-xl text-on-surface">{{ $pendingCount }}</span>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-2">
                    <span class="material-symbols-outlined text-primary">done_all</span>
                    <span class="font-label-numeric text-label-numeric text-on-surface-variant">Prestations terminées</span>
                </div>
                <span class="font-headline-xl text-headline-xl text-on-surface">{{ $counts['completed'] ?? 0 }}</span>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-headline-md text-headline-md text-on-surface">Demandes récentes</h3>
            <div class="flex items-center gap-4">
                <a href="{{ route('provider.services.create') }}" class="text-sm font-button-text font-semibold text-on-primary bg-primary rounded-full px-4 py-2 hover:bg-primary-container transition-colors">
                    Publier un service
                </a>
                <a href="{{ route('requests.index') }}" class="text-sm font-semibold text-primary hover:text-primary-container flex items-center gap-1">
                    Voir tout
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </div>

        @if ($requests->isEmpty())
            <div class="mt-4 bg-surface-container-lowest border border-outline-variant rounded-xl p-12 flex flex-col items-center justify-center text-center">
                <span class="material-symbols-outlined text-5xl text-outline mb-3">inbox</span>
                <p class="text-on-surface-variant">Vous n'avez pas encore reçu de demande.</p>
            </div>
        @else
            <div class="mt-4 bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-surface-container-low transition-colors">
                        <div class="min-w-0">
                            <p class="font-medium text-on-surface truncate">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</p>
                            <p class="text-sm text-on-surface-variant truncate">
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
