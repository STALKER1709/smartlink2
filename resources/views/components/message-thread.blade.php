@props(['conversation'])

<div class="bg-surface-container-lowest rounded-xl border border-outline-variant flex flex-col" style="height: 26rem;">
    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-surface">
        @forelse ($conversation->messages as $message)
            <div class="flex {{ $message->sender_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] rounded-2xl {{ $message->sender_id === Auth::id() ? 'rounded-tr-sm bg-primary text-on-primary' : 'rounded-tl-sm bg-surface-container-lowest border border-outline-variant text-on-surface' }} px-4 py-2.5 text-sm shadow-sm">
                    <p class="whitespace-pre-line">{{ $message->body }}</p>
                    <p class="mt-1 text-[10px] {{ $message->sender_id === Auth::id() ? 'text-on-primary/70' : 'text-on-surface-variant' }}">
                        {{ $message->sender?->name }} · {{ $message->created_at->format('d/m H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-on-surface-variant text-center mt-4">Aucun message pour le moment.</p>
        @endforelse
    </div>

    <form action="{{ route('conversations.messages.store', $conversation) }}" method="POST" class="border-t border-outline-variant p-3 flex gap-2">
        @csrf
        <input
            type="text"
            name="body"
            required
            maxlength="5000"
            placeholder="Écrivez votre message…"
            class="flex-1 rounded-full border-outline-variant text-sm focus:border-primary focus:ring-primary"
        >
        <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors flex items-center justify-center">
            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">send</span>
        </button>
    </form>
</div>
