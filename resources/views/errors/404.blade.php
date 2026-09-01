{{-- Une 404 n'est pas une panne : le routage a fonctionné, l'application va
     bien, c'est l'adresse qui ne mène nulle part. --}}
@php
    $titre = __("Cette page n'existe pas");
    $message = __("Le lien que vous avez suivi est peut-être ancien, ou l'adresse comporte une faute de frappe.");
@endphp

<x-guest-layout :titre="__('Page introuvable')" :indexable="false">
    <x-error-panel icone="search" :titre="$titre" :message="$message">
        {{-- Le catalogue plutôt que l'accueil : quelqu'un qui cherchait une
             page cherchait presque toujours une prestation. --}}
        <x-primary-button :href="route('services.index')" class="w-full justify-center">
            {{ __('Parcourir les services') }}
        </x-primary-button>

        @auth
            <x-secondary-button :href="route('dashboard')" class="w-full justify-center">
                {{ __('Aller à mon tableau de bord') }}
            </x-secondary-button>
        @endauth
    </x-error-panel>
</x-guest-layout>
