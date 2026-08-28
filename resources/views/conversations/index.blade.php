<x-app-layout titre="Messages" :indexable="false">
    <x-slot name="header">
        <x-page-header title="Messages" subtitle="Gérez vos conversations en cours" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-6">
        <form action="{{ route('conversations.index') }}" method="GET"
              class="mb-6 flex w-full items-center rounded-lg border border-outline-variant bg-surface-container px-4 py-3 transition-all focus-within:border-primary focus-within:ring-1 focus-within:ring-primary md:rounded-full md:py-2">
            <span class="material-symbols-outlined mr-3 text-on-surface-variant" aria-hidden="true">search</span>
            <label for="q" class="sr-only">Rechercher une conversation</label>
            <input type="text" id="q" name="q" value="{{ $terme }}"
                   placeholder="Rechercher une conversation…"
                   class="w-full border-none bg-transparent p-0 font-body-md text-body-md text-on-background placeholder:text-on-surface-variant focus:ring-0">
        </form>

        @if ($conversations->isEmpty())
            <x-empty-state :title="$terme !== '' ? 'Aucune conversation ne correspond à « '.$terme.' ».' : 'Aucune conversation pour le moment.'"
                           :description="$terme !== '' ? 'Essayez le nom du prestataire ou le titre du service.' : 'Une conversation s\'ouvre dès qu\'une demande est acceptée.'" />
        @else
            {{-- La rangée non lue se teinte de vert clair et porte son heure en
                 vert : c'est ce qui distingue, d'un balayage, le fil qui
                 attend une réponse. --}}
            <div class="flex flex-col border-t border-outline-variant">
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->otherParticipant(Auth::user());
                        $nom = $other->providerProfile?->business_name ?? $other->name;
                        $dernier = $conversation->latestMessage;
                        $nonLus = $conversation->unread_count ?? 0;
                        $quand = $conversation->last_message_at ?? $conversation->created_at;
                    @endphp
                    <a href="{{ route('conversations.show', $conversation) }}"
                       @class([
                           'flex items-start border-b border-outline-variant p-4 transition-colors duration-200',
                           'bg-secondary-container/20 hover:bg-secondary-container/30' => $nonLus > 0,
                           'hover:bg-surface-container-low' => $nonLus === 0,
                       ])>
                        <div class="relative mr-4 shrink-0">
                            <div class="h-12 w-12 overflow-hidden rounded-full border border-outline-variant bg-surface-container md:h-14 md:w-14">
                                @if ($other->providerProfile?->logo_path)
                                    <img src="{{ media_url($other->providerProfile->logo_path) }}" alt="" loading="lazy"
                                         class="h-full w-full object-cover" onerror="this.remove()">
                                @else
                                    <div class="flex h-full w-full items-center justify-center font-semibold text-on-surface-variant">
                                        {{ Str::upper(Str::substr($nom, 0, 2)) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-baseline justify-between gap-2">
                                <h3 class="truncate font-headline-md text-body-lg text-on-background">{{ $nom }}</h3>
                                <span @class([
                                    'shrink-0 whitespace-nowrap font-label-numeric text-label-numeric',
                                    'text-primary' => $nonLus > 0,
                                    'text-on-surface-variant' => $nonLus === 0,
                                ])>{{ $quand->translatedFormat('d/m') }}</span>
                            </div>

                            <div class="mb-1 truncate font-button-text text-body-md text-on-surface-variant">
                                Demande : {{ $conversation->request?->service?->title ?? 'Demande directe' }}
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <p @class([
                                    'min-w-0 flex-1 truncate font-body-md text-body-md',
                                    'font-semibold text-on-surface' => $nonLus > 0,
                                    'text-on-surface-variant' => $nonLus === 0,
                                ])>
                                    @if ($dernier)
                                        @if ($dernier->sender_id === Auth::id())<span class="text-on-surface-variant">Vous : </span>@endif{{ $dernier->body }}
                                    @else
                                        Aucun message échangé.
                                    @endif
                                </p>

                                @if ($nonLus > 0)
                                    <span class="inline-flex h-6 min-w-6 shrink-0 items-center justify-center rounded-full bg-primary px-1.5 font-label-numeric text-xs font-bold text-on-primary"
                                          aria-label="{{ $nonLus }} message(s) non lu(s)">{{ $nonLus }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
