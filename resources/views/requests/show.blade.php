@php
    $statusLabels = \App\Support\RequestStatus::labels();
@endphp

<x-app-layout :titre="__('Détail de la demande')" :indexable="false">
    <x-slot name="header">
        {{-- « Demande n° 28 » : le client ne connaît pas ce numéro et ne
             s'en sert jamais. Il reconnaît sa demande à la prestation
             qu'il a demandée. Le numéro reste, en second, pour le support. --}}
        <x-page-header
            :title="$serviceRequest->service?->title ?? 'Demande directe'"
            :back="route('requests.index')"
            back-label="Mes demandes"
        >
            <x-slot name="subtitle">
                {{-- La date porte son pictogramme, comme dans la maquette : sur
                     une fiche, c'est le repère qu'on cherche en premier. --}}
                <span class="flex items-center gap-2 text-on-surface-variant">
                    <x-icon name="calendar_today" />
                    <span class="font-label-numeric">{{ $serviceRequest->created_at->translatedFormat('j F Y \à H\hi') }}</span>
                    <span aria-hidden="true" class="text-outline">·</span>
                    <span class="font-label-numeric">n° {{ $serviceRequest->id }}</span>
                </span>
            </x-slot>
            <x-slot name="action">
                <x-status-chip :status="$serviceRequest->status" />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
                {{-- La carte d'interlocuteur : qui est en face, son métier,
                     sa note, et de quoi ouvrir son profil. Les deux colonnes
                     « Client » / « Prestataire » disaient à chacun son propre
                     nom, ce qu'il connaît déjà. --}}
                @php
                    $moiClient = Auth::id() === $serviceRequest->client_id;
                    $autre = $moiClient ? $serviceRequest->provider : $serviceRequest->client;
                    $profil = $moiClient ? $autre?->providerProfile : null;
                    $nomAutre = $profil?->business_name
                        ?? $autre?->clientProfile?->fullName()
                        ?? $autre?->name;
                @endphp

                @if ($autre)
                    {{-- Le bouton passe sous la ligne quand la place manque :
                         côte à côte à 390 px, il réduisait « Soudure &
                         ferronnerie • 4,0 » à trois lignes. --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-4">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-full border border-outline-variant bg-surface-container-high">
                                @if ($profil?->logo_path)
                                    <img src="{{ media_url($profil->logo_path) }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                                @else
                                    <div class="flex h-full w-full items-center justify-center font-semibold text-on-surface-variant">
                                        {{ Str::upper(Str::substr($nomAutre ?? '?', 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-headline-md text-headline-md text-on-surface">{{ $nomAutre }}</h3>
                                {{-- La note passe par son composant. Écrite en clair,
                                     l'étoile « ★ » tombait dans un paragraphe en chasse
                                     fixe, police qui ne porte pas ce caractère : le
                                     navigateur en cherchait une autre, et à 390 px le
                                     glyphe se retrouvait seul sur sa ligne. --}}
                                <p class="flex flex-wrap items-center gap-x-2 font-label-md text-label-md text-on-surface-variant">
                                    <span>{{ $profil?->category?->name ?? ($moiClient ? 'Prestataire' : 'Client') }}</span>
                                    @if ($profil?->rating_count)
                                        <span aria-hidden="true">•</span>
                                        <x-star-rating :rating="$profil->rating_avg" :count="$profil->rating_count" compact />
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($profil)
                            <a href="{{ route('providers.show', $profil) }}"
                               class="shrink-0 rounded-lg border border-primary px-4 py-2 font-button-text text-button-text text-primary transition-colors hover:bg-surface-container-low">
                                {{ __("Voir profil") }}
                            </a>
                        @endif
                    </div>
                @endif

                @if ($serviceRequest->message)
                    <div class="rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-low p-4 md:p-6">
                        <h3 class="mb-2 flex items-center gap-2 font-button-text text-button-text text-on-surface">
                            <x-icon name="description" />
                            {{ __("Message initial") }}
                        </h3>
                        <p class="italic text-on-surface-variant">« {{ $serviceRequest->message }} »</p>
                    </div>
                @endif

                @if ($serviceRequest->preferred_date || $serviceRequest->service)
                    <div class="rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-4 md:p-6">
                        <dl class="grid grid-cols-1 gap-3 text-label-md sm:grid-cols-2">
                            @if ($serviceRequest->service)
                                <div>
                                    <dt class="text-on-surface-variant">{{ __("Service concerné") }}</dt>
                                    <dd><a href="{{ route('services.show', $serviceRequest->service) }}" class="font-medium text-primary hover:text-primary-container">{{ $serviceRequest->service->title }}</a></dd>
                                </div>
                            @endif
                            @if ($serviceRequest->preferred_date)
                                <div>
                                    <dt class="text-on-surface-variant">{{ __("Date souhaitée") }}</dt>
                                    <dd class="font-label-numeric font-medium text-on-surface">{{ $serviceRequest->preferred_date->translatedFormat('j F Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif

                <!-- Review -->
                @can('create', [\App\Models\Review::class, $serviceRequest])
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-3">{{ __("Laisser un avis") }}</h3>
                        <form action="{{ route('requests.review', $serviceRequest) }}" method="POST" class="space-y-3">
                            @csrf
                            {{-- La note se choisissait dans une liste
                                 déroulante « Choisir une note ». C'est le seul
                                 moment où un client s'exprime sur la
                                 plateforme : il mérite des étoiles, pas un
                                 menu. Des boutons radio, donc — l'étoile reste
                                 cliquable sans JavaScript. --}}
                            <fieldset>
                                <legend class="block font-medium text-label-md text-on-surface-variant">{{ __("Votre note") }}</legend>
                                <div class="mt-1 flex flex-row-reverse justify-end gap-1">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" required class="peer sr-only">
                                        <label for="rating-{{ $i }}"
                                               class="cursor-pointer rounded-full p-1 text-outline-variant transition-colors hover:text-secondary-container peer-checked:text-secondary-container peer-focus-visible:ring-2 peer-focus-visible:ring-primary"
                                               title="{{ trans_choice(':count étoile|:count étoiles', $i) }}">
                                            <span class="sr-only">{{ trans_choice(':count étoile|:count étoiles', $i) }}</span>
                                            <x-icon name="star" size="xl" filled class="block" />
                                        </label>
                                    @endfor
                                </div>
                                <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                            </fieldset>
                            <div>
                                <x-input-label for="comment" :value="__('Commentaire (facultatif)')" />
                                <textarea id="comment" name="comment" rows="3" maxlength="1000" class="mt-1 block w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary"></textarea>
                                <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                            </div>
                            <x-primary-button type="submit">{{ __("Envoyer mon avis") }}</x-primary-button>
                        </form>
                    </div>
                @endcan

                @if ($serviceRequest->review)
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-2">{{ __("Votre avis") }}</h3>
                        <x-star-rating :rating="$serviceRequest->review->rating" />
                        @if ($serviceRequest->review->comment)
                            <p class="mt-2 text-label-md text-on-surface">{{ $serviceRequest->review->comment }}</p>
                        @endif
                    </div>
                @endif

                <!-- Status history -->
                <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
                    <h3 class="font-headline-md text-headline-md text-on-surface mb-4">{{ __("Historique") }}</h3>
                    <ol class="space-y-4">
                        @foreach ($serviceRequest->statusHistory as $history)
                            <li class="flex gap-3">
                                <div class="h-2 w-2 mt-1.5 rounded-full bg-primary shrink-0"></div>
                                <div>
                                    <p class="text-label-md text-on-surface">
                                        @if ($history->from_status)
                                            {{ $statusLabels[$history->from_status] ?? $history->from_status }} →
                                        @endif
                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    <p class="text-label-sm text-on-surface-variant">
                                        {{ $history->created_at->format('d/m/Y H:i') }}
                                        @if ($history->changedBy)
                                            · {{ $history->changedBy->name }}
                                        @endif
                                    </p>
                                    @if ($history->note)
                                        <p class="text-label-sm text-on-surface-variant mt-0.5">{{ $history->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Signaler ne s'offre que si le litige peut être reçu :
                     la demande engagée, et pas déjà un signalement ouvert. --}}
                @can('create', [\App\Models\Dispute::class, $serviceRequest])
                    <a href="{{ route('disputes.create', $serviceRequest) }}"
                       class="inline-flex min-h-6 items-center gap-2 text-label-md font-medium text-on-surface-variant transition-colors hover:text-error">
                        <x-icon name="flag" />
                        {{ __("Signaler un problème sur cette demande") }}
                    </a>
                @endcan
            </div>

            <!-- Conversation -->
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-4">{{ __("Conversation") }}</h3>
                @if ($serviceRequest->conversation)
                    <x-message-thread :conversation="$serviceRequest->conversation" />
                @else
                    <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6 text-label-md text-on-surface-variant">
                        {{ __("La conversation s'ouvrira automatiquement une fois la demande acceptée par le prestataire.") }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
