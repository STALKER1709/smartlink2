@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'Retour'])

{{-- En-tête de page unifié : titre à gauche, actions à droite, et un lien de
     retour quand la page est une feuille de navigation. Sur mobile les actions
     passent sous le titre plutôt que de le comprimer. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-flex items-center gap-1 text-label-lg text-on-surface-variant hover:text-primary">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                {{ $backLabel }}
            </a>
        @endif

        <h2 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h2>

        {{-- Le sous-titre accepte une chaîne ou un fragment : certaines fiches
             y placent la date avec son pictogramme, d'autres une simple
             phrase. --}}
        @if ($subtitle)
            <div class="mt-0.5 text-label-lg text-on-surface-variant">{{ $subtitle }}</div>
        @endif
    </div>

    @isset($action)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $action }}
        </div>
    @endisset
</div>
