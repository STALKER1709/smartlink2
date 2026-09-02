<x-app-layout :titre="__('Mes demandes')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Demandes')" :subtitle="$requests->total().' '.Str::plural('demande', $requests->total())">
            @if (Auth::user()->isClient())
                <x-slot name="action">
                    <x-primary-button :href="route('services.index')">
                        <x-icon name="add" />
                        {{ __("Nouvelle demande") }}
                    </x-primary-button>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        @php
            $statuts = collect(\App\Support\RequestStatus::labels())
                ->filter(fn ($label, $valeur) => ($comptes[$valeur] ?? 0) > 0);
        @endphp

        {{-- La rangée de filtres défile latéralement plutôt que de passer à la
             ligne : six pastilles ne tiennent pas sur 390 px, et deux rangées
             coûtaient cent pixels avant le premier résultat. Elle déborde des
             marges pour que la dernière pastille laisse voir qu'il y a une
             suite. --}}
        @if ($statuts->count() > 1)
            <div class="hide-scrollbar -mx-margin-mobile mb-6 flex gap-3 overflow-x-auto px-margin-mobile pb-2 md:mx-0 md:px-0">
                <a href="{{ route('requests.index') }}"
                   @class([
                       'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 font-button-text text-button-text transition-colors',
                       'border border-transparent bg-primary-container text-on-primary-container' => ! request('status'),
                       'border border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-low' => request('status'),
                   ])>
                    {{ __("Toutes") }}
                    <span class="font-label-numeric">{{ array_sum($comptes) }}</span>
                </a>
                @foreach ($statuts as $valeur => $label)
                    <a href="{{ route('requests.index', ['status' => $valeur]) }}"
                       @class([
                           'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 font-button-text text-button-text transition-colors',
                           'border border-transparent bg-primary-container text-on-primary-container' => request('status') === $valeur,
                           'border border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-low' => request('status') !== $valeur,
                       ])>
                        {{ $label }}
                        <span class="font-label-numeric">{{ $comptes[$valeur] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($requests->isEmpty())
            <x-empty-state :title="__('Aucune demande pour le moment.')"
                           :description="__('Les demandes que vous envoyez — et celles que vous recevez — s\'affichent ici.')"
                           :action-label="Auth::user()->isClient() ? __('Parcourir les services') : null"
                           :action-href="Auth::user()->isClient() ? route('services.index') : null" />
        @else
            {{-- La carte de demande des maquettes : qui, quel métier, dans quel
                 état, quoi, et un extrait du message — c'est lui qui distingue
                 deux demandes au même prestataire. --}}
            <div class="flex flex-col gap-4">
                @foreach ($requests as $serviceRequest)
                    @php
                        $client = Auth::user()->isProvider();
                        $autre = $client ? $serviceRequest->client : $serviceRequest->provider;
                        $nom = $client
                            ? ($autre?->clientProfile?->fullName() ?? $autre?->name)
                            : ($autre?->providerProfile?->business_name ?? $autre?->name);
                        $metier = $client
                            ? $serviceRequest->service?->category?->name
                            : ($autre?->providerProfile?->category?->name ?? $serviceRequest->service?->category?->name);
                        $logo = $client ? null : $autre?->providerProfile?->logo_path;
                    @endphp
                    <a href="{{ route('requests.show', $serviceRequest) }}"
                       class="group relative flex flex-col rounded-xl border border-outline-variant shadow-elevation-1 bg-surface p-4 transition-colors hover:bg-surface-container-lowest">
                        @if (($serviceRequest->unread_count ?? 0) > 0)
                            <span class="absolute right-4 top-4 h-3 w-3 rounded-full bg-error"
                                  role="img" aria-label="{{ __('Nouveau message') }}"></span>
                        @endif

                        <div class="mb-3 flex items-center gap-3">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-surface-variant">
                                @if ($logo)
                                    <img src="{{ media_url($logo) }}" alt="" loading="lazy" class="h-full w-full object-cover" onerror="this.remove()">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-surface-container-high font-headline-md font-bold text-on-surface-variant">
                                        {{ $nom ? Str::upper(Str::substr($nom, 0, 2)) : '—' }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h2 class="font-headline-sm text-headline-sm text-on-surface group-hover:text-primary">{{ $nom ?? 'Non assigné' }}</h2>
                                @if ($metier)
                                    <p class="font-body-md text-label-numeric text-on-surface-variant">{{ $metier }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-status-badge :status="$serviceRequest->status" variant="caps" class="mb-2" />
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $serviceRequest->service?->title ?? 'Demande directe' }}</h3>
                            @if ($serviceRequest->message)
                                {{-- La mesure : à pleine largeur d'écran, ce message atteignait
                                     128 signes par ligne, et l'œil perd la ligne suivante
                                     en revenant à la marge. --}}
                                <p class="mt-1 line-clamp-2 max-w-prose font-body-md text-body-md text-on-surface-variant">{{ $serviceRequest->message }}</p>
                            @endif
                        </div>

                        <div class="mt-auto flex items-center justify-between border-t border-outline-variant pt-3">
                            <span class="font-label-numeric text-label-numeric text-on-surface-variant">
                                {{ $serviceRequest->created_at->translatedFormat('d M, H:i') }}
                            </span>
                            <x-icon name="chevron_right" class="text-on-surface-variant transition-transform group-hover:translate-x-0.5" />
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
