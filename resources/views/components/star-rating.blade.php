@props(['rating' => 0, 'count' => null, 'compact' => false])

@php
    $rounded = (int) round((float) $rating);
@endphp

@if ($compact)
    {{-- Sur une carte étroite, cinq étoiles et « (12 avis) » ne tiennent pas :
         une étoile pleine et la note suffisent à porter l'information. --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
        <span class="material-symbols-outlined text-[16px] text-[#fbbc04]" style="font-variation-settings: 'FILL' 1;">star</span>
        <span class="font-label-numeric text-label-numeric text-on-surface">{{ number_format((float) $rating, 1) }}</span>
        @if (! is_null($count))
            <span class="text-xs text-on-surface-variant">({{ $count }})</span>
        @endif
    </span>
@else
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
@endif
