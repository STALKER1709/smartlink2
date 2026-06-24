<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Messages</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($conversations->isEmpty())
            <p class="text-gray-500">Aucune conversation pour le moment.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
                @foreach ($conversations as $conversation)
                    @php $other = $conversation->otherParticipant(Auth::user()); @endphp
                    <a href="{{ route('conversations.show', $conversation) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50">
                        <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                            <span class="text-gray-400 font-semibold">{{ Str::substr($other->providerProfile?->business_name ?? $other->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">
                                {{ $other->providerProfile?->business_name ?? $other->name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $conversation->request?->service?->title ?? 'Demande directe' }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">
                            {{ ($conversation->last_message_at ?? $conversation->created_at)->format('d/m/Y') }}
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
