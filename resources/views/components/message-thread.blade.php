@props(['conversation'])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 flex flex-col" style="height: 26rem;">
    <div class="flex-1 overflow-y-auto p-4 space-y-3">
        @forelse ($conversation->messages as $message)
            <div class="flex {{ $message->sender_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] rounded-lg px-3 py-2 text-sm {{ $message->sender_id === Auth::id() ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                    <p class="whitespace-pre-line">{{ $message->body }}</p>
                    <p class="mt-1 text-[10px] {{ $message->sender_id === Auth::id() ? 'text-brand-200' : 'text-gray-400' }}">
                        {{ $message->sender?->name }} · {{ $message->created_at->format('d/m H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center mt-4">Aucun message pour le moment.</p>
        @endforelse
    </div>

    <form action="{{ route('conversations.messages.store', $conversation) }}" method="POST" class="border-t border-gray-200 p-3 flex gap-2">
        @csrf
        <input
            type="text"
            name="body"
            required
            maxlength="5000"
            placeholder="Écrivez votre message…"
            class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
        >
        <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
            Envoyer
        </button>
    </form>
</div>
