@props(['cols' => 4])

@php
    /*
     * Une rangée de chiffres séparés par des filets, à fond perdu sur les
     * bords de l'écran.
     *
     * Les filets ne sont pas des bordures : c'est le fond du conteneur qui
     * transparaît dans un écart d'un pixel entre les colonnes. `divide-x`,
     * qui semblait fait pour ça, pose une bordure à gauche de tout enfant
     * sauf le premier — dans une grille à deux colonnes, cela met un trait
     * contre le bord gauche du conteneur dès la deuxième rangée, et aucun
     * trait entre les rangées. L'écart d'un pixel, lui, ne connaît ni
     * l'index ni le palier : il sépare exactement ce qui est voisin.
     *
     * Le débord latéral (`-mx`) rend au premier chiffre l'alignement avec
     * le titre de la page : le rembourrage de la colonne retombe pile sur
     * la marge du conteneur.
     *
     * Une conséquence à connaître : une rangée incomplète laisse voir le
     * fond du conteneur en aplat. Le nombre de tuiles doit être un multiple
     * du nombre de colonnes à chaque palier.
     */
    $paliers = [
        4 => 'sm:grid-cols-4',
        6 => 'sm:grid-cols-3 lg:grid-cols-6',
    ];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', [
    '-mx-margin-mobile grid grid-cols-2 gap-px border-y border-outline-variant bg-outline-variant md:-mx-margin-desktop',
    '[&>*]:bg-surface [&>*]:px-margin-mobile md:[&>*]:px-margin-desktop',
    $paliers[$cols] ?? $paliers[4],
])]) }}>
    {{ $slot }}
</div>
