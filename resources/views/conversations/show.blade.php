@php
    $other = $conversation->otherParticipant(Auth::user());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-md text-headline-md text-on-surface">
                {{ $other->providerProfile?->business_name ?? $other->name }}
            </h2>
            @if ($conversation->request)
                <a href="{{ route('requests.show', $conversation->request) }}" class="text-sm font-semibold text-primary hover:text-primary-container">
                    Voir la demande →
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <x-message-thread :conversation="$conversation" />
    </div>
</x-app-layout>
