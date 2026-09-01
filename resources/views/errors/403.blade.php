@php
    $utilisateur = Auth::user();

    // Laravel pose « This action is unauthorized. » quand la Policy s'est
    // contentée de rendre false. Ce n'est pas une explication, et c'est en
    // anglais : on ne l'affiche pas. Une Policy qui a quelque chose à dire
    // passe par Response::deny(), et son message arrive ici.
    $explication = $exception?->getMessage();
    $explication = ($explication === null || $explication === '' || $explication === 'This action is unauthorized.')
        ? null
        : $explication;
@endphp

<x-guest-layout :titre="__('Accès refusé')" :indexable="false">
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
            <x-icon name="lock" size="xl" />
        </span>

        <h1 class="mt-4 font-headline-md text-headline-md text-on-surface">{{ __("Cette page ne vous est pas ouverte") }}</h1>

        <p class="mt-2 text-label-md text-on-surface-variant">
            {{ $explication ?? __("Votre compte n'a pas accès à cette page.") }}
        </p>
    </div>

    {{-- Une page de refus sans sortie est une impasse : on y arrive souvent
         par un lien légitime — ici, le bouton « Faire une demande » suivi
         depuis une fiche, puis une connexion avec un compte prestataire. --}}
    <div class="mt-6 space-y-3">
        @auth
            <x-primary-button :href="route('dashboard')" class="w-full justify-center">
                {{ __("Aller à mon tableau de bord") }}
            </x-primary-button>

            @if ($utilisateur?->isProvider())
                <x-secondary-button :href="route('provider.services.index')" class="w-full justify-center">
                    {{ __("Mes services") }}
                </x-secondary-button>
            @else
                <x-secondary-button :href="route('services.index')" class="w-full justify-center">
                    {{ __("Parcourir les services") }}
                </x-secondary-button>
            @endif
        @else
            <x-primary-button :href="route('login')" class="w-full justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        @endauth
    </div>
</x-guest-layout>
