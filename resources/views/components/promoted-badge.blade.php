@props(['profile'])

@if ($profile?->is_promoted)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-tertiary-container/15 px-2 py-0.5 text-xs font-semibold text-tertiary']) }}
        title="{{ __('ui.plans.promoted_hint') }}"
    >
        <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
        {{ __('ui.plans.promoted_badge') }}
    </span>
@endif
