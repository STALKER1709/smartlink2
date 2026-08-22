@php
    $other = $conversation->otherParticipant(Auth::user());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $other->providerProfile?->business_name ?? $other->name }}
            </h2>
            @if ($conversation->request)
                <a href="{{ route('requests.show', $conversation->request) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Voir la demande →
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <x-message-thread :conversation="$conversation" />
    </div>
</x-app-layout>
