<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Notifications">
            @if ($notifications->isNotEmpty())
                <x-slot name="action">
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant px-4 py-2 text-sm font-semibold text-primary transition-colors hover:border-primary/50">
                            <span class="material-symbols-outlined text-base">done_all</span>
                            Tout marquer comme lu
                        </button>
                    </form>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($notifications->isEmpty())
            <x-empty-state title="Aucune notification pour le moment." />
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $link = match ($data['type'] ?? null) {
                            'request.new', 'request.status_changed' => route('requests.show', $data['request_id']),
                            'message.new' => route('conversations.show', $data['conversation_id']),
                            'review.new' => route('dashboard'),
                            default => null,
                        };
                    @endphp
                    <div class="flex items-start gap-3 p-4 {{ $notification->read_at ? '' : 'bg-secondary-container/25' }}">
                        <div class="h-2 w-2 mt-2 rounded-full {{ $notification->read_at ? 'bg-outline-variant' : 'bg-primary' }} shrink-0"></div>

                        <div class="flex-1 min-w-0">
                            @if ($link)
                                <a href="{{ $link }}" class="text-sm text-on-surface hover:text-primary">
                                    {{ $data['message'] ?? 'Notification' }}
                                </a>
                            @else
                                <p class="text-sm text-on-surface">{{ $data['message'] ?? 'Notification' }}</p>
                            @endif
                            <p class="text-xs text-on-surface-variant mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        @if (! $notification->read_at)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-on-surface-variant hover:text-on-surface">
                                    Marquer comme lu
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
