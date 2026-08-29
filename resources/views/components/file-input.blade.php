@props([
    'name',
    'accept' => null,
    'multiple' => false,
    // Le défaut se résout dans le corps, pas ici : un @props est évalué avant
    // que la locale ne soit posée pour la vue.
    'label' => null,
    'hint' => 'Aucun fichier choisi',
])

{{--
    Le champ de fichier du navigateur, habillé.

    Laissé nu, il affichait « Choose File · No file chosen » — en anglais, au
    gabarit du système, au milieu d'un formulaire français entièrement
    dessiné. C'était l'élément qui trahissait le plus l'inachevé sur toute
    l'application.

    Le `<label>` déclenche nativement le sélecteur : sans JavaScript le champ
    reste utilisable, seul le nom du fichier choisi ne s'affiche pas.
--}}
<div x-data="{ noms: [] }" {{ $attributes->only('class') }}>
    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-outline-variant bg-surface-container-lowest px-4 py-2 font-button-text text-sm font-semibold text-primary transition-colors hover:bg-surface-container-low focus-within:ring-2 focus-within:ring-primary focus-within:ring-offset-2">
        <span class="material-symbols-outlined text-base" aria-hidden="true">upload</span>
        {{ $label ?? __('Choisir un fichier') }}
        <input
            type="file"
            name="{{ $multiple ? $name.'[]' : $name }}"
            @if ($accept) accept="{{ $accept }}" @endif
            @if ($multiple) multiple @endif
            {{ $attributes->except('class') }}
            class="sr-only"
            x-on:change="noms = Array.from($event.target.files).map(f => f.name)"
        >
    </label>

    <p class="mt-1.5 text-sm text-on-surface-variant"
       x-text="noms.length ? noms.join(' · ') : {{ Js::from($hint) }}">{{ $hint }}</p>
</div>
