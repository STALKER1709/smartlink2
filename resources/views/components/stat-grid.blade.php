@props(['cols' => 4])

@php
    /*
     * La grille de chiffres des maquettes Stitch : des cartes bordées
     * séparées par une gouttière, deux colonnes sur mobile.
     *
     * Elle a remplacé une grille à filets, où les colonnes se séparaient par
     * un écart d'un pixel sur le fond du conteneur. Les maquettes posent des
     * cartes ; c'est leur motif, et il vaut pour les trois tableaux de bord.
     */
    $paliers = [
        4 => 'md:grid-cols-4',
        6 => 'sm:grid-cols-3 lg:grid-cols-6',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-4 md:gap-gutter '.($paliers[$cols] ?? $paliers[4])]) }}>
    {{ $slot }}
</div>
