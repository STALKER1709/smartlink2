@php
    // Laravel pose « This action is unauthorized. » quand la Policy s'est
    // contentée de rendre false. Ce n'est pas une explication, et c'est en
    // anglais : on ne l'affiche pas. Une Policy qui a quelque chose à dire
    // passe par Response::deny(), et son message arrive ici.
    $explication = $exception?->getMessage();
    $explication = ($explication === null || $explication === '' || $explication === 'This action is unauthorized.')
        ? null
        : $explication;

    $message = $explication ?? __("Votre compte n'a pas accès à cette page.");
@endphp

<x-guest-layout :titre="__('Accès refusé')" :indexable="false">
    <x-error-panel icone="lock"
                   :titre="__('Cette page ne vous est pas ouverte')"
                   :message="$message">
        @auth
            <x-primary-button :href="route('dashboard')" class="w-full justify-center">
                {{ __('Aller à mon tableau de bord') }}
            </x-primary-button>

            @if (Auth::user()?->isProvider())
                <x-secondary-button :href="route('provider.services.index')" class="w-full justify-center">
                    {{ __('Mes services') }}
                </x-secondary-button>
            @else
                <x-secondary-button :href="route('services.index')" class="w-full justify-center">
                    {{ __('Parcourir les services') }}
                </x-secondary-button>
            @endif
        @else
            <x-primary-button :href="route('login')" class="w-full justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        @endauth
    </x-error-panel>
</x-guest-layout>
