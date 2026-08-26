@php
    $statusLabels = \App\Support\RequestStatus::labels();
@endphp

<x-app-layout>
    <x-slot name="header">
        {{-- « Demande n° 28 » : le client ne connaît pas ce numéro et ne
             s'en sert jamais. Il reconnaît sa demande à la prestation
             qu'il a demandée. Le numéro reste, en second, pour le support. --}}
        <x-page-header
            :title="$serviceRequest->service?->title ?? 'Demande directe'"
            :subtitle="$serviceRequest->created_at->translatedFormat('j F Y').' · demande n° '.$serviceRequest->id"
            :back="route('requests.index')"
            back-label="Mes demandes"
        >
            <x-slot name="action">
                <x-status-badge :status="$serviceRequest->status" />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
                <!-- Details -->
                <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                    @if ($serviceRequest->service)
                        <p class="text-sm text-on-surface-variant">Service concerné</p>
                        <a href="{{ route('services.show', $serviceRequest->service) }}" class="font-medium text-primary hover:text-primary-container">
                            {{ $serviceRequest->service->title }}
                        </a>
                    @else
                        <p class="text-sm text-on-surface-variant">Demande directe (sans service précis)</p>
                    @endif

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-on-surface-variant">Client</p>
                            <p class="font-medium text-on-surface">
                                {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-on-surface-variant">Prestataire</p>
                            <p class="font-medium text-on-surface">
                                {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                            </p>
                        </div>
                        @if ($serviceRequest->preferred_date)
                            <div>
                                <p class="text-on-surface-variant">Date souhaitée</p>
                                <p class="font-medium text-on-surface">{{ $serviceRequest->preferred_date->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-on-surface-variant">Envoyée le</p>
                            <p class="font-medium text-on-surface">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-on-surface-variant text-sm">Message</p>
                        <p class="mt-1 text-on-surface whitespace-pre-line">{{ $serviceRequest->message }}</p>
                    </div>
                </div>

                <!-- Actions -->
                @php $canRespond = Auth::user()->can('respond', $serviceRequest); @endphp
                @if ($canRespond || Auth::user()->can('cancel', $serviceRequest))
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface">Actions</h3>

                        {{-- L'action attendue d'abord, seule et en pleine largeur.
                             Refus et annulation sont des gestes qu'on ne fait pas
                             par mégarde : ils demandent d'ouvrir le repli, ce qui
                             laisse aussi la place d'écrire un motif. --}}
                        <div class="mt-4 space-y-3">
                            @if ($canRespond && in_array($serviceRequest->status, ['sent', 'viewed'], true))
                                <form action="{{ route('requests.accept', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <x-primary-button type="submit" class="w-full sm:w-auto">
                                        <span class="material-symbols-outlined text-base">check</span>
                                        Accepter la demande
                                    </x-primary-button>
                                </form>
                            @endif

                            @if ($canRespond && $serviceRequest->status === 'accepted')
                                <form action="{{ route('requests.start', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-tertiary px-6 py-2.5 font-button-text text-button-text text-on-tertiary transition-all duration-150 hover:opacity-90 active:scale-95 sm:w-auto">
                                        <span class="material-symbols-outlined text-base">play_arrow</span>
                                        Démarrer la prestation
                                    </button>
                                </form>
                            @endif

                            @if ($canRespond && $serviceRequest->status === 'in_progress')
                                <form action="{{ route('requests.complete', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <x-primary-button type="submit" class="w-full sm:w-auto">
                                        <span class="material-symbols-outlined text-base">done_all</span>
                                        Marquer comme terminée
                                    </x-primary-button>
                                </form>
                            @endif
                        </div>

                        @php
                            $peutRefuser = $canRespond && in_array($serviceRequest->status, ['sent', 'viewed'], true);
                            $peutAnnuler = Auth::user()->can('cancel', $serviceRequest);
                        @endphp

                        @if ($peutRefuser || $peutAnnuler)
                            <details class="group mt-4 border-t border-outline-variant pt-4">
                                <summary class="flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-on-surface-variant hover:text-on-surface">
                                    <span class="material-symbols-outlined text-base transition-transform group-open:rotate-180">expand_more</span>
                                    {{ $peutRefuser ? 'Refuser ou annuler' : 'Annuler la demande' }}
                                </summary>

                                <div class="mt-3 space-y-4">
                                    @if ($peutRefuser)
                                        <form action="{{ route('requests.refuse', $serviceRequest) }}" method="POST" class="space-y-2">
                                            @csrf
                                            <label for="refuse-reason" class="block text-sm text-on-surface-variant">Motif du refus (facultatif)</label>
                                            <input id="refuse-reason" type="text" name="reason" maxlength="500" placeholder="Je ne suis pas disponible à cette date…"
                                                   class="block w-full rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                                            <x-danger-button type="submit">Refuser la demande</x-danger-button>
                                        </form>
                                    @endif

                                    @if ($peutAnnuler)
                                        <form action="{{ route('requests.cancel', $serviceRequest) }}" method="POST" class="space-y-2">
                                            @csrf
                                            <label for="cancel-reason" class="block text-sm text-on-surface-variant">Motif de l'annulation (facultatif)</label>
                                            <input id="cancel-reason" type="text" name="reason" maxlength="500" placeholder="Je n'ai plus besoin de cette prestation…"
                                                   class="block w-full rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                                            <x-secondary-button type="submit">Annuler la demande</x-secondary-button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </div>
                @endif

                <!-- Review -->
                @can('create', [\App\Models\Review::class, $serviceRequest])
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-3">Laisser un avis</h3>
                        <form action="{{ route('requests.review', $serviceRequest) }}" method="POST" class="space-y-3">
                            @csrf
                            {{-- La note se choisissait dans une liste
                                 déroulante « Choisir une note ». C'est le seul
                                 moment où un client s'exprime sur la
                                 plateforme : il mérite des étoiles, pas un
                                 menu. Des boutons radio, donc — l'étoile reste
                                 cliquable sans JavaScript. --}}
                            <fieldset>
                                <legend class="block font-medium text-sm text-on-surface-variant">Votre note</legend>
                                <div class="mt-1 flex flex-row-reverse justify-end gap-1">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" required class="peer sr-only">
                                        <label for="rating-{{ $i }}"
                                               class="cursor-pointer rounded-full p-1 text-outline-variant transition-colors hover:text-tertiary-container peer-checked:text-tertiary-container peer-focus-visible:ring-2 peer-focus-visible:ring-primary"
                                               title="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}">
                                            <span class="sr-only">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</span>
                                            <span class="material-symbols-outlined block text-[30px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">star</span>
                                        </label>
                                    @endfor
                                </div>
                                <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                            </fieldset>
                            <div>
                                <x-input-label for="comment" value="Commentaire (facultatif)" />
                                <textarea id="comment" name="comment" rows="3" maxlength="1000" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary"></textarea>
                                <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                            </div>
                            <x-primary-button type="submit">Envoyer mon avis</x-primary-button>
                        </form>
                    </div>
                @endcan

                @if ($serviceRequest->review)
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-2">Votre avis</h3>
                        <x-star-rating :rating="$serviceRequest->review->rating" />
                        @if ($serviceRequest->review->comment)
                            <p class="mt-2 text-sm text-on-surface">{{ $serviceRequest->review->comment }}</p>
                        @endif
                    </div>
                @endif

                <!-- Status history -->
                <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Historique</h3>
                    <ol class="space-y-4">
                        @foreach ($serviceRequest->statusHistory as $history)
                            <li class="flex gap-3">
                                <div class="h-2 w-2 mt-1.5 rounded-full bg-primary shrink-0"></div>
                                <div>
                                    <p class="text-sm text-on-surface">
                                        @if ($history->from_status)
                                            {{ $statusLabels[$history->from_status] ?? $history->from_status }} →
                                        @endif
                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    <p class="text-xs text-on-surface-variant">
                                        {{ $history->created_at->format('d/m/Y H:i') }}
                                        @if ($history->changedBy)
                                            · {{ $history->changedBy->name }}
                                        @endif
                                    </p>
                                    @if ($history->note)
                                        <p class="text-xs text-on-surface-variant mt-0.5">{{ $history->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <!-- Conversation -->
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Conversation</h3>
                @if ($serviceRequest->conversation)
                    <x-message-thread :conversation="$serviceRequest->conversation" />
                @else
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6 text-sm text-on-surface-variant">
                        La conversation s'ouvrira automatiquement une fois la demande acceptée par le prestataire.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
