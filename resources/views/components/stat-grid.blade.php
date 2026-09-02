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
        /*
         * Six de front demandent 1 280 px de *contenu*, pas de fenêtre — et
         * une requête de média mesure la fenêtre. Sur un écran d'administration
         * la colonne latérale en prend 256 : à `xl`, il restait 1 056 px, soit
         * 160 px par tuile, et chacun des six libellés passait à la ligne.
         * Trois de front jusqu'à `2xl`, six au-delà.
         */
        6 => 'sm:grid-cols-3 2xl:grid-cols-6',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-4 md:gap-gutter '.($paliers[$cols] ?? $paliers[4])]) }}>
    {{ $slot }}
</div>
