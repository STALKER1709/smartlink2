@props([
    'label',
    'value',
    'href' => null,
    'tone' => 'secondary',
    'hint' => null,
    'hintTone' => null,
    'texte' => false,
])

@php
    $tag = $href ? 'a' : 'div';
    $tons = [
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'tertiary' => 'text-tertiary',
        'error' => 'text-error',
    ];
@endphp

{{--
    Un chiffre, son libellé, et de quoi le situer.

    C'était une carte bordée avec une icône, répétée quatre fois — le gabarit
    « grand nombre, petit libellé, accent coloré » que produit n'importe quel
    générateur de tableau de bord. Les boîtes ont disparu : les chiffres se
    séparent par un filet, comme les colonnes d'un relevé. Ce qui compte est le
    chiffre, pas le cadre autour.

    `texte` pour une valeur qui n'est pas un nombre. « Essentiel » composé
    au corps d'un grand chiffre écrasait les « 3 », « 1 » et « 2 » de la même
    rangée : la colonne la moins chiffrée devenait la plus voyante.

    L'icône aussi a disparu, et avec elle sa propriété : quatre
    pictogrammes décoratifs alignés n'apprenaient rien que le libellé ne
    disait déjà.

    Le rembourrage latéral appartient à la grille (`x-stat-grid`), qui seule
    sait où tombe le bord de l'écran.
--}}
<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group block py-4'.($href ? ' transition-colors hover:bg-surface-container-low/60' : '')]) }}
>
    <span @class([
        'block font-headline-xl leading-none tracking-tight',
        'text-3xl sm:text-headline-xl' => ! $texte,
        'text-headline-md' => $texte,
        $tons[$tone] ?? $tons['secondary'],
    ])>{{ $value }}</span>

    <span @class([
        'mt-2 block text-sm font-medium leading-tight text-on-surface',
        'group-hover:text-primary' => $href,
    ])>{{ $label }}</span>

    {{-- La ligne d'indication est toujours réservée, même vide : les colonnes
         d'une rangée s'étirent à la hauteur de la plus haute, et sans cette
         réserve les chiffres cessent de s'aligner.

         Elle peut porter un ton — l'ambre d'un plafond atteint, par exemple.
         C'est l'indication qui l'énonce, pas le chiffre : teinter le « 3 »
         de « 3 sur 3 » laisserait croire que trois est en soi un problème. --}}
    <span @class([
        'mt-0.5 block min-h-[1rem] text-xs',
        $tons[$hintTone] ?? 'text-on-surface-variant',
        'font-medium' => $hintTone !== null,
    ])>{{ $hint }}</span>
</{{ $tag }}>
