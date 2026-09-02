@php
    $other = $conversation->otherParticipant(Auth::user());
    $nom = $other->providerProfile?->business_name ?? $other->name;
@endphp

<x-app-layout :assistant="false" :pied-de-page="false" :indexable="false">
    <x-slot name="header">
        <x-page-header
            :title="$nom"
            :back="route('conversations.index')"
            back-label="Messages"
        >
            @if ($conversation->request)
                <x-slot name="action">
                    <x-secondary-button :href="route('requests.show', $conversation->request)">
                        {{ __("Voir la demande") }}
                        <x-icon name="arrow_forward" size="sm" />
                    </x-secondary-button>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-6 md:px-margin-tablet md:py-8 lg:px-margin-desktop">
        <div class="lg:grid lg:grid-cols-[minmax(0,360px)_1fr] lg:items-start lg:gap-6">
            {{-- La colonne des fils suit le défilement : sur un fil long, elle
                 restait sinon tout en haut de la page. Elle est masquée sous
                 `lg`, où le fil ouvert occupe l'écran entier. --}}
            <div class="hidden lg:sticky lg:top-24 lg:block lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto">
                @include('partials.conversation-list', ['conversations' => $fils, 'actif' => $conversation->id])
            </div>

            <div class="min-w-0">
                {{-- Le bandeau de contexte de la maquette : de quelle demande
                     on parle, et où elle en est. Il remplace la phrase centrée
                     qui ouvrait le fil — celle-là défilait avec les messages et
                     disparaissait au troisième échange. --}}
                @if ($conversation->request)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3">
                        <p class="flex min-w-0 items-center gap-2 font-label-md text-label-md text-on-surface-variant">
                            <x-icon name="handyman" size="sm" class="shrink-0 text-secondary" />
                            <span class="truncate">{{ $conversation->request->service?->title ?? __('Demande directe') }}</span>
                        </p>
                        <x-status-badge :status="$conversation->request->status" />
                    </div>
                @endif

                <x-message-thread :conversation="$conversation" plein />
            </div>
        </div>
    </div>
</x-app-layout>
