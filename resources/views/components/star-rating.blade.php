@props(['rating' => 0, 'count' => null])

@php
    $rounded = (int) round((float) $rating);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <span class="flex text-[#fbbc04]">
        @for ($i = 1; $i <= 5; $i++)
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' {{ $i <= $rounded ? 1 : 0 }};">star</span>
        @endfor
    </span>
    <span class="font-label-numeric text-label-numeric text-on-surface-variant">
        {{ number_format((float) $rating, 1) }}
        @if (! is_null($count))
            ({{ $count }} avis)
        @endif
    </span>
</span>
