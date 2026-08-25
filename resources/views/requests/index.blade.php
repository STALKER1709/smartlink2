<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Demandes" :subtitle="$requests->total().' '.Str::plural('demande', $requests->total())">
            @if (Auth::user()->isClient())
                <x-slot name="action">
                    <a href="{{ route('requests.create') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                        <span class="material-symbols-outlined text-base">add</span>
                        Nouvelle demande
                    </a>
                </x-slot>
            @endif
        </x-page-header>
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
            <x-empty-state icon="inbox" title="Aucune demande pour le moment." description="Les demandes que vous envoyez — et celles que vous recevez — s'affichent ici." />
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
