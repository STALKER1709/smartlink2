<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tableau de bord" />
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
            <x-stat-tile label="Services publiés" :value="$servicesCount" icon="home_repair_service"
                         :href="route('provider.services.index')" />
            <x-stat-tile label="Services actifs" :value="$activeServicesCount" icon="check_circle" tone="primary"
                         :href="route('provider.services.index')" />
            <x-stat-tile label="Demandes en attente" :value="$pendingCount" icon="pending_actions" tone="tertiary"
                         :href="route('requests.index', ['status' => 'sent'])" />
            <x-stat-tile label="Prestations terminées" :value="$counts['completed'] ?? 0" icon="done_all" tone="primary"
                         :href="route('requests.index', ['status' => 'completed'])" />
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-headline-md text-headline-md text-on-surface">Demandes récentes</h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('provider.services.create') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                    <span class="material-symbols-outlined text-base">add</span>
                    Publier un service
                </a>
                <a href="{{ route('requests.index') }}" class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-container">
                    Voir tout
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </div>

        @if ($requests->isEmpty())
            <x-empty-state
                class="mt-4"
                icon="inbox"
                title="Vous n'avez pas encore reçu de demande."
                description="Publiez vos services et soignez votre profil : les clients vous trouveront par la recherche."
                action-label="Publier un service"
                :action-href="route('provider.services.create')"
            />
        @else
            <div class="mt-4 divide-y divide-outline-variant overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                @foreach ($requests as $serviceRequest)
                    <a href="{{ route('requests.show', $serviceRequest) }}" class="group flex items-center gap-4 p-4 transition-colors hover:bg-surface-container-low">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-on-surface group-hover:text-primary">
                                {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                            </p>
                            <p class="truncate text-sm text-on-surface-variant">
                                {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                                · <span class="font-label-numeric">{{ $serviceRequest->created_at->format('d/m/Y') }}</span>
                            </p>
                        </div>
                        <x-status-badge :status="$serviceRequest->status" class="shrink-0" />
                        <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform group-hover:translate-x-0.5">chevron_right</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
