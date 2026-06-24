@props(['rating' => 0, 'count' => null])

@php
    $rounded = (int) round((float) $rating);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}>
    <span class="flex text-amber-400">
        @for ($i = 1; $i <= 5; $i++)
            @if ($i <= $rounded)
                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1 1 5.8L10 14.9l-5.21 2.62 1-5.8-4.21-4.1 5.82-.85L10 1.5z"/></svg>
            @else
                <svg class="h-4 w-4 fill-current text-gray-300" viewBox="0 0 20 20"><path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1 1 5.8L10 14.9l-5.21 2.62 1-5.8-4.21-4.1 5.82-.85L10 1.5z"/></svg>
            @endif
        @endfor
    </span>
    <span class="text-sm text-gray-600">
        {{ number_format((float) $rating, 1) }}
        @if (! is_null($count))
            ({{ $count }} avis)
        @endif
    </span>
</span>
