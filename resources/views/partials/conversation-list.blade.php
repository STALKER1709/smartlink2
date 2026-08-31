{{--
    La colonne des fils, partagée par l'index et la conversation ouverte.

    Attend : $conversations (liste ou paginateur), et éventuellement $terme et
    $actif (l'identifiant du fil ouvert).

    Recopiée dans les deux vues, elle aurait dérivé au premier ajout d'un seul
    côté — c'est ce qui était arrivé aux boutons primaires du dépôt.
--}}
@php
    $terme = $terme ?? '';
    $actif = $actif ?? null;
@endphp

<div class="flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
    <div class="border-b border-outline-variant p-3">
        <form action="{{ route('conversations.index') }}" method="GET" class="relative">
            <x-icon name="search" size="sm" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-outline" />
            <label for="q" class="sr-only">{{ __("Rechercher une conversation") }}</label>
            <input type="text" id="q" name="q" value="{{ $terme }}"
                   placeholder="{{ __('Rechercher une conversation…') }}"
                   class="w-full rounded-full border-outline-variant bg-surface py-2 pl-10 pr-4 font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant focus:border-primary focus:ring-1 focus:ring-primary">
        </form>
    </div>

    <div class="flex flex-col divide-y divide-outline-variant">
        @foreach ($conversations as $conversation)
            @php
                $other = $conversation->otherParticipant(Auth::user());
                $nom = $other->providerProfile?->business_name ?? $other->name;
                $dernier = $conversation->latestMessage;
                $nonLus = $conversation->unread_count ?? 0;
                $quand = $conversation->last_message_at ?? $conversation->created_at;
                $ouvert = $actif === $conversation->id;
            @endphp

            {{-- Un seul fond, celui du fil ouvert. Le fil non lu se signale
                 par son texte gras, son heure en vert et son compteur, comme
                 dans la maquette : teinté en jaune, il criait plus fort que le
                 fil ouvert, et on ne savait plus lequel on lisait. --}}
            <a href="{{ route('conversations.show', $conversation) }}"
               @class([
                   'flex gap-3 p-4 transition-colors duration-150',
                   'bg-primary-container/20' => $ouvert,
                   'hover:bg-surface-container-low' => ! $ouvert,
               ])
               @if ($ouvert) aria-current="page" @endif>
                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container-high">
                    @if ($other->providerProfile?->logo_path)
                        <img src="{{ media_url($other->providerProfile->logo_path) }}" alt="" loading="lazy"
                             class="h-full w-full object-cover" onerror="this.remove()">
                    @else
                        <span class="font-label-md text-label-md font-bold text-on-surface-variant">{{ Str::upper(Str::substr($nom, 0, 2)) }}</span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="flex items-baseline justify-between gap-2">
                        <span class="truncate font-label-md text-label-md font-bold text-on-surface">{{ $nom }}</span>
                        <span @class([
                            'shrink-0 whitespace-nowrap font-label-numeric text-label-sm',
                            'text-primary' => $nonLus > 0,
                            'text-on-surface-variant' => $nonLus === 0,
                        ])>{{ $quand->translatedFormat('d/m') }}</span>
                    </span>

                    <span class="mt-1 flex items-center justify-between gap-2">
                        <span @class([
                            'min-w-0 flex-1 truncate font-body-md text-body-md',
                            'font-semibold text-on-surface' => $nonLus > 0,
                            'text-on-surface-variant' => $nonLus === 0,
                        ])>
                            @if ($dernier)
                                @if ($dernier->sender_id === Auth::id())<span class="text-on-surface-variant">{{ __("Vous :") }} </span>@endif{{ $dernier->body }}
                            @else
                                {{ __("Aucun message échangé.") }}
                            @endif
                        </span>

                        @if ($nonLus > 0)
                            <span class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full bg-primary px-1.5 font-label-numeric text-label-sm font-bold text-on-primary"
                                  aria-label="{{ __(':n message(s) non lu(s)', ['n' => $nonLus]) }}">{{ $nonLus }}</span>
                        @endif
                    </span>

                    {{-- La pastille de la demande, comme dans la maquette : un
                         fil retrouvé trois semaines plus tard ne dit sinon plus
                         à quoi il se rapporte. --}}
                    <span class="mt-2 inline-flex max-w-full items-center gap-1 truncate rounded-full bg-secondary-container/30 px-2 py-0.5 font-label-sm text-label-sm font-semibold text-on-secondary-container">
                        <x-icon name="handyman" size="xs" />
                        <span class="truncate">{{ $conversation->request?->service?->title ?? __('Demande directe') }}</span>
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</div>
