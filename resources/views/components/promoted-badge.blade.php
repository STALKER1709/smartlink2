@props(['profile', 'taille' => 'sm'])

@php
    // Sur la fiche prestataire, ce badge tient dans une rangée de puces plus
    // grandes ; à la même taille qu'ailleurs il s'y lisait comme une note.
    $gabarit = $taille === 'md'
        ? 'border border-secondary-container px-3 py-1 text-label-md'
        : 'px-2 py-0.5 text-label-sm';
@endphp

@if ($profile?->is_promoted)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-secondary-container/25 font-semibold text-on-secondary-container '.$gabarit]) }}
        title="{{ __('ui.plans.promoted_hint') }}"
    >
        <x-icon name="star" :size="$taille === 'md' ? 'sm' : 'xs'" filled />
        {{ __('ui.plans.promoted_badge') }}
    </span>
@endif
