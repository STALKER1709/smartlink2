<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('Bonjour :prenom', ['prenom' => Str::of(auth()->user()->name)->trim()->explode(' ')->first()])"
                       subtitle="Voici où en sont vos demandes.">
            {{-- Le prestataire a « Publier un service » sur son tableau de
                 bord ; le client n'avait rien. Chercher une prestation est
                 pourtant la seule chose qu'il vient faire. --}}
            <x-slot name="action">
                <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">search</span>
                    Trouver un prestataire
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    @php
        $tuiles = [
            ['sent', 'Envoyées', 'secondary'],
            ['accepted', 'Acceptées', 'secondary'],
            ['in_progress', 'En cours', 'tertiary'],
            ['completed', 'Terminées', 'primary'],
        ];
    @endphp

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        {{-- Chaque tuile mène à la liste filtrée : un compteur qui ne mène nulle
             part oblige à refaire le tri à la main. --}}
        <x-stat-grid>
            @foreach ($tuiles as [$statut, $libelle, $ton])
                <x-stat-tile
                    :label="$libelle"
                    :value="$counts[$statut] ?? 0"
                    :tone="$ton"
                    :href="route('requests.index', ['status' => $statut])"
                />
            @endforeach
        </x-stat-grid>

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
                title="Vous n'avez pas encore envoyé de demande."
                description="Trouvez un prestataire près de chez vous et décrivez-lui votre besoin."
                action-label="Explorer les services"
                :action-href="route('services.index')"
            />
        @else
            <x-list-panel class="mt-4">
                @foreach ($requests as $serviceRequest)
                    <x-list-row>
                        <a href="{{ route('requests.show', $serviceRequest) }}" class="group flex items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="font-medium text-on-surface group-hover:text-primary">
                                        {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                                    </p>
                                    <x-status-badge :status="$serviceRequest->status" />
                                </div>
                                <p class="mt-0.5 text-sm text-on-surface-variant">
                                    {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                                    · <span class="font-label-numeric">{{ $serviceRequest->created_at->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform group-hover:translate-x-0.5" aria-hidden="true">chevron_right</span>
                        </a>
                    </x-list-row>
                @endforeach
            </x-list-panel>

        @endif
    </div>
</x-app-layout>
