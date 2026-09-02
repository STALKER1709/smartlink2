@php
    // La limitation de débit annonce elle-même le délai, dans l'en-tête
    // « Retry-After ». Le dire vaut mieux qu'un « patientez » sans durée :
    // sans chiffre, on réessaie tout de suite, et le compteur repart.
    $secondes = (int) ($exception?->getHeaders()['Retry-After'] ?? 0);
@endphp

<x-guest-layout :titre="__('Trop de tentatives')" :indexable="false">
    <x-error-panel icone="block"
                   :titre="__('Vous allez un peu vite')"
                   :message="$secondes > 0
                       ? __('Trop de tentatives en peu de temps. Réessayez dans :secondes secondes.', ['secondes' => $secondes])
                       : __('Trop de tentatives en peu de temps. Patientez une minute, puis réessayez.')">
        <x-primary-button type="button" onclick="location.reload()" class="w-full">
            {{ __('Réessayer') }}
        </x-primary-button>

        <x-secondary-button :href="route('services.index')" class="w-full justify-center">
            {{ __('Parcourir les services') }}
        </x-secondary-button>
    </x-error-panel>
</x-guest-layout>
