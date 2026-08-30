@props([
    'name',
    'size' => 'md',
    'filled' => false,
    'bold' => false,
    'label' => null,
])

{{--
    L'icône, et une seule façon de l'écrire.

    Trois choses qu'elle règle, chacune constatée dans le dépôt :

    1. **Le sens ou le silence, jamais l'entre-deux.** Une ligature Material
       Symbols est du *texte* : sans attribut, un lecteur d'écran annonce
       « plumbing », « arrow_forward », « star ». Trente icônes étaient dans ce
       cas. Ici le défaut est `aria-hidden` — une icône ne dit rien de plus que
       le libellé qu'elle accompagne — et `label` la rend annonçable quand elle
       est seule à porter l'information.

    2. **Une échelle, pas treize tailles.** Les vues écrivaient `text-base`,
       `text-[18px]`, `text-[20px]`, `text-3xl`… pour la même famille d'objets.

    3. **Le remplissage par une classe, pas par un `style=` en ligne.** Vingt et
       une icônes portaient `style="font-variation-settings: 'FILL' 1"`, ce qui
       remettait au passage les trois autres axes à leur valeur par défaut.
--}}

@php
    $tailles = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'];
    $palier = in_array($size, $tailles, true) ? $size : 'md';

    $classes = array_filter([
        'material-symbols-outlined',
        'icon-'.$palier,
        $filled ? 'icon-filled' : null,
        $bold ? 'icon-bold' : null,
    ]);

    // Une icône nommée est annoncée ; une icône muette est sautée. Il n'y a pas
    // de troisième cas.
    $accessibilite = $label
        ? ['role' => 'img', 'aria-label' => $label]
        : ['aria-hidden' => 'true'];
@endphp

<span {{ $attributes->merge(['class' => implode(' ', $classes)])->merge($accessibilite) }}>{{ $name }}</span>
