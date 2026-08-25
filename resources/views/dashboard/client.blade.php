<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Tableau de bord</h2>
    </x-slot>

    @php
        $tuiles = [
            ['sent', 'Envoyées', 'send', 'secondary'],
            ['accepted', 'Acceptées', 'task_alt', 'secondary'],
            ['in_progress', 'En cours', 'pending_actions', 'tertiary'],
            ['completed', 'Terminées', 'check_circle', 'primary'],
        ];
    @endphp

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        {{-- Chaque tuile mène à la liste filtrée : un compteur qui ne mène nulle
             part oblige à refaire le tri à la main. --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
            @foreach ($tuiles as [$statut, $libelle, $icone, $ton])
                <x-stat-tile
                    :label="$libelle"
                    :value="$counts[$statut] ?? 0"
                    :icon="$icone"
                    :tone="$ton"
                    :href="route('requests.index', ['status' => $statut])"
                />
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between gap-3">
            <h3 class="font-headline-md text-headline-md text-on-surface">Demandes récentes</h3>
            <a href="{{ route('requests.index') }}" class="flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:text-primary-container">
                Voir tout
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        @if ($requests->isEmpty())
            <x-empty-state
                class="mt-4"
                icon="inbox"
                title="Vous n'avez pas encore envoyé de demande."
                description="Trouvez un prestataire près de chez vous et décrivez-lui votre besoin."
                action-label="Explorer les services"
                :action-href="route('services.index')"
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
                                {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
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
