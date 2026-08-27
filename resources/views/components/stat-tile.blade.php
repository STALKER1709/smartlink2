@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'secondary',
    'href' => null,
    'hint' => null,
    'hintTone' => null,
    'variant' => 'inline',
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
    {{ $attributes->merge(['class' => 'flex flex-col justify-between rounded-xl border border-outline-variant bg-surface-container-lowest p-4'.($href ? ' transition-colors hover:border-primary/50 hover:bg-surface-container-low' : '')]) }}
>
    @if ($variant === 'stacked')
        @if ($icon)
            <span class="material-symbols-outlined {{ $teinte }}" aria-hidden="true">{{ $icon }}</span>
        @endif
        {{-- Le chiffre en vert et en chasse fixe, le libellé en dessous :
             c'est la disposition du tableau de bord prestataire. --}}
        <span class="mt-2 block font-headline-xl font-label-numeric text-3xl leading-none text-primary sm:text-headline-xl">{{ $value }}</span>
        <span class="mt-1 block font-body-md text-body-md leading-tight text-on-surface-variant">{{ $label }}</span>
        <span @class([
            'mt-0.5 block min-h-[1rem] text-xs',
            $tons[$hintTone] ?? 'text-on-surface-variant',
            'font-medium' => $hintTone !== null,
        ])>{{ $hint }}</span>
    @else
        <div class="mb-2 flex items-start justify-between gap-2">
            @if ($icon)
                <span class="material-symbols-outlined {{ $teinte }}" aria-hidden="true">{{ $icon }}</span>
            @endif
            <span class="min-w-0 text-right font-label-numeric text-label-numeric text-on-surface-variant">{{ $label }}</span>
        </div>
        <span class="block font-headline-xl text-3xl font-bold leading-none text-on-surface sm:text-headline-xl">{{ $value }}</span>
        @if ($hint)
            <span @class([
                'mt-1 block text-xs',
                $tons[$hintTone] ?? 'text-on-surface-variant',
                'font-medium' => $hintTone !== null,
            ])>{{ $hint }}</span>
        @endif
    @endif
</{{ $tag }}>
