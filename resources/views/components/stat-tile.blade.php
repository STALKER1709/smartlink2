@props([
    'label',
    'value',
    'icon' => null,
    'href' => null,
    'tone' => 'secondary',
    'hint' => null,
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

{{-- Un chiffre seul ne dit pas où cliquer. Quand la tuile mène quelque part,
     elle le montre : survol, flèche, et le curseur qui change. --}}
<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group flex flex-col justify-between gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-4 transition-all'.($href ? ' hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md' : '')]) }}
>
    <div class="flex items-start justify-between gap-2">
        @if ($icon)
            <span class="material-symbols-outlined {{ $tons[$tone] ?? $tons['secondary'] }}">{{ $icon }}</span>
        @endif
        <span class="text-right text-xs font-medium leading-tight text-on-surface-variant">{{ $label }}</span>
    </div>

    <div>
        <span class="font-headline-xl text-headline-xl leading-none text-on-surface">{{ $value }}</span>
        @if ($hint)
            <p class="mt-1 text-xs text-on-surface-variant">{{ $hint }}</p>
        @endif
    </div>
</{{ $tag }}>
