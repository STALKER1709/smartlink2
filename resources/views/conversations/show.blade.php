@php
    $other = $conversation->otherParticipant(Auth::user());
@endphp

<x-app-layout :assistant="false" :pied-de-page="false">
    <x-slot name="header">
        <x-page-header
            :title="$other->providerProfile?->business_name ?? $other->name"
            :back="route('conversations.index')"
            back-label="Messages"
        >
            @if ($conversation->request)
                <x-slot name="action">
                    <a href="{{ route('requests.show', $conversation->request) }}" class="inline-flex items-center gap-1 rounded-full border border-outline-variant px-4 py-2 text-sm font-semibold text-primary transition-colors hover:border-primary/50">
                        Voir la demande
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-3xl px-margin-mobile py-6 md:px-margin-desktop md:py-8">
        <x-message-thread :conversation="$conversation" plein />
    </div>
</x-app-layout>
