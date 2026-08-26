<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Notifications" :subtitle="$nonLues > 0 ? $nonLues.' non '.Str::plural('lue', $nonLues) : null">
            @if ($nonLues > 0)
                <x-slot name="action">
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant px-4 py-2 font-button-text text-sm font-semibold text-primary transition-colors hover:border-primary/50">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">done_all</span>
                            Tout marquer comme lu
                        </button>
                    </form>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($notifications->isEmpty())
            <x-empty-state title="Aucune notification pour le moment."
                           description="Les réponses de vos prestataires et les changements de statut de vos demandes arrivent ici." />
        @else
            <x-list-panel>
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $link = match ($data['type'] ?? null) {
                            'request.new', 'request.status_changed' => route('requests.show', $data['request_id']),
                            'message.new' => route('conversations.show', $data['conversation_id']),
                            'review.new' => route('dashboard'),
                            default => null,
                        };

                        // Les notifications déjà en base portent l'ancienne
                        // phrase — « est passé à "in_progress" », la valeur de
                        // la colonne. Quand la charge utile porte le statut,
                        // la phrase est recomposée à l'affichage plutôt que
                        // laissée telle qu'elle a été écrite.
                        $texte = isset($data['to_status'])
                            ? \App\Notifications\RequestStatusChangedNotification::phrase(
                                $data['service_title'] ?? $titres[$data['request_id'] ?? null] ?? null,
                                $data['to_status'],
                            )
                            : ($data['message'] ?? 'Notification');
                    @endphp

                    <x-list-row @class(['-mx-margin-mobile px-margin-mobile md:-mx-6 md:px-6', 'bg-secondary-container/25' => ! $notification->read_at])>
                        <div class="flex items-start gap-3">
                            {{-- Le fond teinté disait déjà « non lue » : la
                                 pastille ne se répète que pour ceux qui ne
                                 distinguent pas la teinte. --}}
                            <span @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-primary' => ! $notification->read_at,
                                'bg-outline-variant' => $notification->read_at,
                            ]) role="img"
                               aria-label="{{ $notification->read_at ? 'Lue' : 'Non lue' }}"></span>

                            <div class="min-w-0 flex-1">
                                @if ($link)
                                    <a href="{{ $link }}" class="text-body-md text-on-surface hover:text-primary">{{ $texte }}</a>
                                @else
                                    <p class="text-body-md text-on-surface">{{ $texte }}</p>
                                @endif

                                {{-- « Marquer comme lu » se tenait à droite du
                                     texte et lui prenait cent vingt pixels :
                                     chaque message passait à la ligne pour
                                     laisser place à une action secondaire,
                                     répétée à chaque rangée. --}}
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-on-surface-variant">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    @if (! $notification->read_at)
                                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-primary hover:text-primary-container">
                                                Marquer comme lu
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
