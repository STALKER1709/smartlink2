{{-- « 419 | Page Expired » est le message le plus opaque que Laravel produise :
     un nombre qui n'est même pas un code HTTP standard, et deux mots anglais
     qui ne disent ni ce qui s'est passé, ni que la saisie n'est pas perdue.

     Ce qui s'est passé : le jeton anti-CSRF du formulaire a expiré pendant que
     la page restait ouverte. Le geste utile est de revenir en arrière — le
     navigateur y garde les champs remplis — et de renvoyer. --}}
@php
    $message = __("Cette page est restée ouverte un long moment, et sa session de sécurité a expiré. Revenez en arrière : votre saisie y est encore, il suffit de renvoyer le formulaire.");
@endphp

<x-guest-layout :titre="__('Session expirée')" :indexable="false">
    <x-error-panel icone="schedule" :titre="__('Votre page a expiré')" :message="$message">
        <x-primary-button type="button" onclick="history.back()" class="w-full">
            <x-icon name="arrow_back" size="sm" />
            {{ __('Revenir en arrière') }}
        </x-primary-button>

        @auth
            <x-secondary-button :href="route('dashboard')" class="w-full justify-center">
                {{ __('Aller à mon tableau de bord') }}
            </x-secondary-button>
        @else
            <x-secondary-button :href="route('login')" class="w-full justify-center">
                {{ __('Log in') }}
            </x-secondary-button>
        @endauth
    </x-error-panel>
</x-guest-layout>
