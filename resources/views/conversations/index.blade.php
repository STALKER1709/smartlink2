<x-app-layout :titre="__('Messages')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Messages')" :subtitle="__('Gérez vos conversations en cours')" />
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-6 md:px-margin-tablet lg:px-margin-desktop">
        @if ($conversations->isEmpty())
            {{-- Sans fil, pas de deux colonnes : la recherche reste au-dessus
                 de la phrase qui explique l'absence. --}}
            <div class="mx-auto max-w-3xl">
                @include('partials.conversation-list', ['conversations' => $conversations, 'terme' => $terme])

                <x-empty-state compact class="mt-6"
                    :title="$terme !== '' ? __('Aucune conversation ne correspond à « :terme ».', ['terme' => $terme]) : __('Aucune conversation pour le moment.')"
                    :description="$terme !== '' ? __('Essayez le nom du prestataire ou le titre du service.') : __('Une conversation s\'ouvre dès qu\'une demande est acceptée.')" />
            </div>
        @else
            {{-- Deux volets à partir de `lg`, comme dans la maquette. Sur
                 mobile la liste occupe la page entière : le fil s'ouvre sur son
                 propre écran, avec son lien de retour. --}}
            <div class="lg:grid lg:grid-cols-[minmax(0,360px)_1fr] lg:items-start lg:gap-6">
                <div>
                    @include('partials.conversation-list', ['conversations' => $conversations, 'terme' => $terme])

                    <div class="mt-6">{{ $conversations->links() }}</div>
                </div>

                {{-- Le volet de droite n'existe qu'à partir de `lg` : sur
                     mobile, une invitation à choisir un fil ferait défiler la
                     liste hors de l'écran. --}}
                <div class="hidden min-h-[24rem] flex-col items-center justify-center gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center lg:flex">
                    <x-icon name="forum" size="3xl" class="text-outline" />
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ __("Choisissez une conversation") }}</p>
                    <p class="max-w-sm font-body-md text-body-md text-on-surface-variant">
                        {{ __("Le fil s'ouvre ici, à côté de la liste : vous passez de l'un à l'autre sans revenir en arrière.") }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
