@props(['profile', 'taille' => 'sm'])

@php
    // Sur la fiche prestataire, ce badge tient dans une rangée de puces plus
    // grandes ; à la même taille qu'ailleurs il s'y lisait comme une note.
    $gabarit = $taille === 'md'
        ? 'border border-tertiary/20 px-3 py-1 text-sm'
        : 'px-2 py-0.5 text-xs';
@endphp

@if ($profile?->is_promoted)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-tertiary-container/15 font-semibold text-tertiary '.$gabarit]) }}
        title="{{ __('ui.plans.promoted_hint') }}"
    >
        <span class="material-symbols-outlined {{ $taille === 'md' ? 'text-sm' : 'text-xs' }}" style="font-variation-settings: 'FILL' 1;">star</span>
        {{ __('ui.plans.promoted_badge') }}
    </span>
@endif
