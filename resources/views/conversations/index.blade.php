<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Messages" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($conversations->isEmpty())
            <x-empty-state title="Aucune conversation pour le moment." description="Une conversation s'ouvre dès qu'une demande est acceptée." />
        @else
            {{-- La rangée disait avec qui l'on parlait et à quelle date, jamais
                 de quoi ni s'il y avait du neuf : une liste de messagerie sans
                 aperçu ni marque de non-lu oblige à ouvrir chaque fil pour
                 savoir lequel attend une réponse. --}}
            <x-list-panel>
                @foreach ($conversations as $conversation)
                    @php
                        $other = $conversation->otherParticipant(Auth::user());
                        $dernier = $conversation->latestMessage;
                        $nonLus = $conversation->unread_count ?? 0;
                    @endphp
                    <x-list-row>
                        <a href="{{ route('conversations.show', $conversation) }}" class="group flex items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container">
                                <span class="font-semibold text-on-surface-variant">{{ Str::substr($other->providerProfile?->business_name ?? $other->name, 0, 1) }}</span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p @class([
                                        'min-w-0 truncate text-on-surface group-hover:text-primary',
                                        'font-semibold' => $nonLus > 0,
                                        'font-medium' => $nonLus === 0,
                                    ])>{{ $other->providerProfile?->business_name ?? $other->name }}</p>
                                    <span class="shrink-0 font-label-numeric text-xs text-on-surface-variant">
                                        {{ ($conversation->last_message_at ?? $conversation->created_at)->format('d/m/Y') }}
                                    </span>
                                </div>

                                <p class="truncate text-sm text-on-surface-variant">
                                    {{ $conversation->request?->service?->title ?? 'Demande directe' }}
                                </p>

                                <div class="mt-0.5 flex items-center gap-2">
                                    {{-- L'aperçu se tronque, lui : c'est un
                                         extrait, pas un libellé. --}}
                                    <p @class([
                                        'min-w-0 flex-1 truncate text-sm',
                                        'text-on-surface' => $nonLus > 0,
                                        'text-on-surface-variant' => $nonLus === 0,
                                    ])>
                                        @if ($dernier)
                                            @if ($dernier->sender_id === Auth::id())<span class="text-on-surface-variant">Vous : </span>@endif{{ $dernier->body }}
                                        @else
                                            <span class="text-on-surface-variant">Aucun message échangé.</span>
                                        @endif
                                    </p>

                                    @if ($nonLus > 0)
                                        <span class="inline-flex shrink-0 items-center justify-center rounded-full bg-primary px-2 py-0.5 font-label-numeric text-xs font-bold text-on-primary"
                                              aria-label="{{ $nonLus }} message(s) non lu(s)">{{ $nonLus }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
