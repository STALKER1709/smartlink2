<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Messages" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($conversations->isEmpty())
            <x-empty-state icon="forum" title="Aucune conversation pour le moment." description="Une conversation s'ouvre dès qu'une demande est acceptée." />
        @else
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
                @foreach ($conversations as $conversation)
                    @php $other = $conversation->otherParticipant(Auth::user()); @endphp
                    <a href="{{ route('conversations.show', $conversation) }}" class="flex items-center gap-4 p-4 hover:bg-surface-container-low transition-colors">
                        <div class="h-12 w-12 rounded-full bg-surface-container flex items-center justify-center overflow-hidden shrink-0 border border-outline-variant">
                            <span class="text-on-surface-variant font-semibold">{{ Str::substr($other->providerProfile?->business_name ?? $other->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-on-surface truncate">
                                {{ $other->providerProfile?->business_name ?? $other->name }}
                            </p>
                            <p class="text-sm text-on-surface-variant truncate">
                                {{ $conversation->request?->service?->title ?? 'Demande directe' }}
                            </p>
                        </div>
                        <span class="text-xs text-on-surface-variant shrink-0">
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
