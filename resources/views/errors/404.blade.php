{{-- Une 404 n'est pas une panne : le routage a fonctionné, l'application va
     bien, c'est l'adresse qui ne mène nulle part. La page peut donc être une
     page comme les autres, avec la charte et les composants du site. --}}
<x-guest-layout :titre="__('Page introuvable')" :indexable="false">
    <div class="text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
            <x-icon name="search" size="xl" />
        </span>

        <h1 class="mt-4 font-headline-md text-headline-md text-on-surface">{{ __("Cette page n'existe pas") }}</h1>

        <p class="mt-2 text-label-md text-on-surface-variant">
            {{ __("Le lien que vous avez suivi est peut-être ancien, ou l'adresse comporte une faute de frappe.") }}
        </p>
    </div>

    {{-- Le catalogue plutôt que l'accueil : quelqu'un qui cherchait une page
         cherchait presque toujours une prestation. --}}
    <div class="mt-6 space-y-3">
        <x-primary-button :href="route('services.index')" class="w-full justify-center">
            {{ __("Parcourir les services") }}
        </x-primary-button>

        @auth
            <x-secondary-button :href="route('dashboard')" class="w-full justify-center">
                {{ __("Aller à mon tableau de bord") }}
            </x-secondary-button>
        @endauth
    </div>
</x-guest-layout>
