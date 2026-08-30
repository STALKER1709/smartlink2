@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'secondary',
    'href' => null,
    'hint' => null,
    'hintTone' => null,
    'variant' => 'inline',
    'trend' => null,
    'alert' => false,
])

@php
    $tag = $href ? 'a' : 'div';
    $tons = [
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'tertiary' => 'text-tertiary',
        'error' => 'text-error',
    ];
    $teinte = $tons[$tone] ?? $tons['secondary'];
@endphp

{{--
    La tuile de statistique des maquettes Stitch : une carte bordée, le
    pictogramme du métier en haut à gauche, le libellé en face, le chiffre en
    grand dessous.

    Deux dispositions, toutes deux issues des maquettes : `inline` (icône et
    libellé sur la même ligne, chiffre en dessous — tableaux de bord client et
    administration) et `stacked` (icône, puis chiffre, puis libellé et son
    indication — tableau de bord prestataire, où les libellés sont longs et
    portent une précision).
--}}
<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    @class([
        'group relative flex flex-col justify-between overflow-hidden rounded-xl border p-4',
        // Le compteur qui doit alerter prend le fond de l'erreur : c'est la
        // seule tuile des maquettes qui sorte du blanc.
        'border-error/20 bg-error-container text-on-error-container' => $alert,
        'border-outline-variant bg-surface-container-lowest shadow-elevation-1' => ! $alert,
        'transition-colors hover:border-primary/50 hover:shadow-elevation-2' => (bool) $href,
    ])
    {{ $attributes->except('class') }}
>
    {{-- L'arc de couleur en coin : la signature des cartes de la maquette.
         À 5 % il ne se lit pas comme un aplat mais comme une lumière — et
         c'est ce qui distingue une carte dessinée d'une boîte bordée. Il est
         décoratif, donc `aria-hidden`, et il ne bouge pas au survol : la
         charte refuse qu'un élément se déplace sous le curseur. --}}
    @unless ($alert)
        <span aria-hidden="true"
              class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-bl-full bg-primary/5 transition-colors group-hover:bg-primary/10"></span>
    @endunless

    <span class="relative">
    @if ($variant === 'stacked')
        @if ($icon)
            <x-icon :name="$icon" class="{{ $teinte }}" />
        @endif
        {{-- Le chiffre en vert et en chasse fixe, le libellé en dessous :
             c'est la disposition du tableau de bord prestataire. --}}
        <span class="mt-2 block font-label-numeric text-headline-lg leading-none text-primary sm:text-display-lg">{{ $value }}</span>
        <span class="mt-1 block font-body-md text-body-md leading-tight text-on-surface-variant">{{ $label }}</span>
        <span @class([
            'mt-0.5 block min-h-[1rem] text-label-sm',
            $tons[$hintTone] ?? 'text-on-surface-variant',
            'font-medium' => $hintTone !== null,
        ])>{{ $hint }}</span>
    @else
        <div class="mb-2 flex items-start justify-between gap-2">
            @if ($icon)
                <x-icon :name="$icon" class="{{ $teinte }}" />
            @endif
            <span class="min-w-0 text-right font-label-numeric text-label-numeric text-on-surface-variant">{{ $label }}</span>
        </div>
        <span @class([
            'block font-label-numeric text-headline-lg font-bold leading-none sm:text-display-lg',
            'text-on-error-container' => $alert,
            'text-on-surface' => ! $alert,
        ])>{{ $value }}</span>

        {{-- La tendance : une flèche et un pourcentage, muets quand la période
             précédente ne permet pas de comparer. --}}
        @if ($trend !== null)
            <span @class(['mt-1 flex items-center gap-1', 'text-primary' => $trend >= 0, 'text-error' => $trend < 0])>
                <x-icon :name="$trend >= 0 ? 'trending_up' : 'trending_down'" size="xs" />
                <span class="font-label-numeric text-label-sm">{{ $trend > 0 ? '+' : '' }}{{ $trend }} %</span>
            </span>
        @endif

        @if ($hint)
            <span @class([
                'mt-1 block text-label-sm',
                $tons[$hintTone] ?? 'text-on-surface-variant',
                'font-medium' => $hintTone !== null,
            ])>{{ $hint }}</span>
        @endif
    @endif
    </span>
</{{ $tag }}>
