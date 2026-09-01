@props([
    'name',
    'size' => 'md',
    'filled' => false,
    'label' => null,
])

{{--
    L'icône, et une seule façon de l'écrire.

    Le nom appartient au vocabulaire de l'application — `handyman`,
    `location_on`, `check_circle` — et `config/icons.php` le traduit en nom
    Font Awesome. Une vue ignore donc quelle fonte est employée, et en changer
    ne toucherait que cette table.

    Trois choses réglées ici, chacune constatée dans le dépôt :

    1. **Le sens ou le silence, jamais l'entre-deux.** Le défaut est
       `aria-hidden` — une icône ne dit rien de plus que le libellé qu'elle
       accompagne — et `label` la rend annonçable quand elle est seule à porter
       l'information. Le glyphe est posé par `content` sur un pseudo-élément :
       il n'est plus du texte, et un lecteur d'écran n'a plus rien à y lire.
       La forme précédente employait des ligatures, et trente icônes se
       faisaient annoncer « plumbing », « arrow_forward », « star ».

    2. **Une échelle, pas treize tailles.** Les vues écrivaient `text-base`,
       `text-[18px]`, `text-[20px]`, `text-3xl`… pour la même famille d'objets.
       Une taille d'icône est une dimension : elle est donnée en pixels et ne
       suit pas le palier typographique du texte autour.

    3. **Le remplissage ne se demande que là où il veut dire quelque chose.**
       `filled` ne s'applique qu'aux icônes binaires — l'étoile d'une note, le
       cœur d'un favori — dont le remplissage dit l'état. Ailleurs il ne
       voulait rien dire, et Font Awesome Free ne dessine de toute façon que
       27 des 97 icônes d'ici en contour : mélanger les deux donnerait un jeu
       dont un quart des pictogrammes est plus léger que les autres.
--}}

@php
    $tailles = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'];
    $palier = in_array($size, $tailles, true) ? $size : 'md';

    $glyphe = config('icons.correspondance')[$name] ?? null;
    $binaire = in_array($name, config('icons.binaires', []), true);

    $classes = array_filter([
        'icone',
        $glyphe ? 'icone-'.$glyphe : null,
        'icone-taille-'.$palier,
        // Le contour est l'état « vide » d'une icône binaire ; partout
        // ailleurs il n'existe pas.
        $binaire && ! $filled ? 'icone-contour' : null,
    ]);

    // Une icône nommée est annoncée ; une icône muette est sautée. Il n'y a pas
    // de troisième cas.
    $accessibilite = $label
        ? ['role' => 'img', 'aria-label' => $label]
        : ['aria-hidden' => 'true'];
@endphp

<span {{ $attributes->merge(['class' => implode(' ', $classes)])->merge($accessibilite) }}></span>
