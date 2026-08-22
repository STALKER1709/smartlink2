@props(['profile'])

@if ($profile?->is_promoted)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900']) }}
        title="{{ __('ui.plans.promoted_hint') }}"
    >
        <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.363 1.118l1.286 3.958c.3.922-.755 1.688-1.539 1.118l-3.366-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.286-3.958a1 1 0 00-.364-1.118L2.063 9.385c-.783-.57-.38-1.81.588-1.81h4.161a1 1 0 00.951-.69l1.286-3.958z" />
        </svg>
        {{ __('ui.plans.promoted_badge') }}
    </span>
@endif
