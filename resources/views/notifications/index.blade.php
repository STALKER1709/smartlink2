<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
            @if ($notifications->isNotEmpty())
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-brand-600 hover:text-brand-800">
                        Tout marquer comme lu
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($notifications->isEmpty())
            <p class="text-gray-500">Aucune notification pour le moment.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
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
                    <div class="flex items-start gap-3 p-4 {{ $notification->read_at ? '' : 'bg-brand-50' }}">
                        <div class="h-2 w-2 mt-2 rounded-full {{ $notification->read_at ? 'bg-gray-300' : 'bg-brand-600' }} shrink-0"></div>

                        <div class="flex-1 min-w-0">
                            @if ($link)
                                <a href="{{ $link }}" class="text-sm text-gray-900 hover:text-brand-600">
                                    {{ $data['message'] ?? 'Notification' }}
                                </a>
                            @else
                                <p class="text-sm text-gray-900">{{ $data['message'] ?? 'Notification' }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        @if (! $notification->read_at)
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">
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
