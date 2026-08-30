@php
    /*
     * Chaque type de notification porte son pictogramme et sa pastille de
     * couleur, comme dans les maquettes : on reconnaît d'un coup d'œil un
     * changement de statut, un message et un avis.
     */
    $apparences = [
        'request.status_changed' => ['check_circle', 'bg-primary text-on-primary'],
        'request.new' => ['assignment', 'bg-primary text-on-primary'],
        'message.new' => ['chat', 'bg-tertiary-container text-on-tertiary'],
        'review.new' => ['star', 'bg-secondary-container/30 text-on-secondary-container'],
    ];
@endphp

<x-app-layout :titre="__('Notifications')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Notifications')" :subtitle="$nonLues > 0 ? $nonLues.' non '.Str::plural('lue', $nonLues) : null">
            @if ($nonLues > 0)
                <x-slot name="action">
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="cursor-pointer border-none bg-transparent p-0 font-button-text text-button-text text-primary transition-opacity hover:text-primary-container active:opacity-80">
                            {{ __("Tout marquer comme lu") }}
                        </button>
                    </form>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-6">
        @if ($notifications->isEmpty())
            <x-empty-state :title="__('Aucune notification pour le moment.')"
                           :description="__('Les réponses de vos prestataires et les changements de statut de vos demandes arrivent ici.')" />
        @else
            <div class="flex flex-col gap-3">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $type = $data['type'] ?? null;
                        [$icone, $pastille] = $apparences[$type] ?? ['notifications', 'bg-surface-variant text-on-surface-variant'];
                        $lue = (bool) $notification->read_at;

                        $link = match ($type) {
                            'request.new', 'request.status_changed' => route('requests.show', $data['request_id']),
                            'message.new' => route('conversations.show', $data['conversation_id']),
                            'review.new' => route('dashboard'),
                            default => null,
                        };

                        // Les notifications déjà en base portent l'ancienne
                        // phrase — « est passé à "in_progress" », la valeur de
                        // la colonne. Quand la charge utile porte le statut,
                        // la phrase est recomposée à l'affichage.
                        $texte = isset($data['to_status'])
                            ? \App\Notifications\RequestStatusChangedNotification::phrase(
                                $data['service_title'] ?? $titres[$data['request_id'] ?? null] ?? null,
                                $data['to_status'],
                            )
                            : ($data['message'] ?? 'Notification');
                    @endphp

                    <{{ $link ? 'a' : 'div' }} @if ($link) href="{{ $link }}" @endif
                        @class([
                            'group relative flex items-start gap-4 rounded-lg border border-outline-variant p-4 transition-colors',
                            'bg-primary-container/10 hover:bg-primary-container/15' => ! $lue,
                            'bg-surface hover:bg-surface-container-low' => $lue,
                        ])>
                        @unless ($lue)
                            <span class="absolute left-2 top-1/2 h-2 w-2 -translate-y-1/2 rounded-full bg-primary"
                                  role="img" aria-label="{{ __('Non lue') }}"></span>
                        @endunless

                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $pastille }}">
                            <x-icon :name="$icone" />
                        </span>

                        <div class="min-w-0 flex-grow">
                            <p @class([
                                'mb-1 font-body-md text-body-md',
                                'text-on-surface' => ! $lue,
                                'text-on-surface-variant' => $lue,
                            ])>{{ $texte }}</p>

                            <div class="flex flex-wrap items-center gap-x-3">
                                <span class="block font-label-md text-label-md text-on-surface-variant">{{ $notification->created_at->diffForHumans() }}</span>
                                @unless ($lue)
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-label-sm font-medium text-primary hover:text-primary-container">
                                            {{ __("Marquer comme lu") }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </{{ $link ? 'a' : 'div' }}>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
