@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Retour', 'heading' => 'h1'])

{{-- En-tête de page unifié : titre à gauche, actions à droite, et un lien de
     retour quand la page est une feuille de navigation. Sur mobile les actions
     passent sous le titre plutôt que de le comprimer. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 md:flex-row md:items-center md:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}"
               class="group -ml-2 mb-1 inline-flex min-h-11 items-center gap-1 rounded-lg px-2 text-label-md text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">
                <x-icon name="arrow_back" size="sm" class="transition-transform duration-150 group-hover:-translate-x-0.5" />
                {{ $backLabel }}
            </a>
        @endif

        {{-- La maquette donne au titre de page le palier au-dessus de celui
             d'une carte, et un sous-titre en corps de lecture : c'est ce qui
             distingue l'en-tête d'un écran d'un simple titre de section. --}}
        {{-- `h1` par défaut : c'est le titre de l'écran, et rien d'autre ne
             le porte. La règle précédente réservait le premier niveau aux
             pages publiques en supposant que le contenu s'en chargeait
             ailleurs — un relevé des vingt-six écrans a montré que dix-sept
             n'avaient aucun `h1`. Un lecteur d'écran commence alors sa lecture
             par un titre de deuxième niveau, sans savoir de quelle page il
             s'agit. `heading` reste réglable pour un écran qui porterait
             réellement son titre ailleurs. --}}
        <{{ $heading }} class="font-headline-lg text-headline-md text-on-surface md:text-headline-lg">{{ $title }}</{{ $heading }}>

        {{-- Le sous-titre accepte une chaîne ou un fragment : certaines fiches
             y placent la date avec son pictogramme, d'autres une simple
             phrase. --}}
        @if ($subtitle)
            <div class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ $subtitle }}</div>
        @endif
    </div>

    @isset($action)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $action }}
        </div>
    @endisset
</div>
