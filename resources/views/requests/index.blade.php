<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Demandes" :subtitle="$requests->total().' '.Str::plural('demande', $requests->total())">
            @if (Auth::user()->isClient())
                <x-slot name="action">
                    <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">add</span>
                        Nouvelle demande
                    </a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @php
            $statuts = collect(\App\Support\RequestStatus::labels())
                ->filter(fn ($label, $valeur) => ($comptes[$valeur] ?? 0) > 0);
        @endphp

        {{-- Les neuf pastilles se proposaient toutes, sur deux rangées, quel
             que soit le contenu — plusieurs ne renvoyaient rien. Ne restent
             que les statuts présents, avec leur nombre. --}}
        @if ($statuts->count() > 1)
            <div class="mb-6 flex flex-wrap gap-2">
                <a href="{{ route('requests.index') }}"
                   @class([
                       'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-button-text text-xs font-semibold transition-colors',
                       'bg-primary text-on-primary' => ! request('status'),
                       'bg-surface-container-high text-on-surface-variant hover:bg-surface-container' => request('status'),
                   ])>
                    Toutes
                    <span class="font-label-numeric">{{ array_sum($comptes) }}</span>
                </a>
                @foreach ($statuts as $valeur => $label)
                    <a href="{{ route('requests.index', ['status' => $valeur]) }}"
                       @class([
                           'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 font-button-text text-xs font-semibold transition-colors',
                           'bg-primary text-on-primary' => request('status') === $valeur,
                           'bg-surface-container-high text-on-surface-variant hover:bg-surface-container' => request('status') !== $valeur,
                       ])>
                        {{ $label }}
                        <span class="font-label-numeric">{{ $comptes[$valeur] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($requests->isEmpty())
            <x-empty-state title="Aucune demande pour le moment."
                           description="Les demandes que vous envoyez — et celles que vous recevez — s'affichent ici."
                           :action-label="Auth::user()->isClient() ? 'Parcourir les services' : null"
                           :action-href="Auth::user()->isClient() ? route('services.index') : null" />
        @else
            <x-list-panel>
                @foreach ($requests as $serviceRequest)
                    <x-list-row>
                        <a href="{{ route('requests.show', $serviceRequest) }}" class="group flex items-start gap-4">
                            <div class="min-w-0 flex-1">
                                {{-- Le titre ne se tronque plus : c'est par lui
                                     qu'on reconnaît sa propre demande. --}}
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="font-medium text-on-surface group-hover:text-primary">
                                        {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                                    </p>
                                    <x-status-badge :status="$serviceRequest->status" />
                                </div>
                                <p class="mt-0.5 text-sm text-on-surface-variant">
                                    @if (Auth::user()->isProvider())
                                        {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                                    @else
                                        {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                                    @endif
                                    · <span class="font-label-numeric">{{ $serviceRequest->created_at->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform group-hover:translate-x-0.5" aria-hidden="true">chevron_right</span>
                        </a>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
