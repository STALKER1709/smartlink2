@props([
    'icon' => 'search_off',
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

{{-- Une liste vide sans explication laisse croire à une panne. Ce bloc dit ce
     qui manque, pourquoi, et propose la sortie la plus probable. --}}
<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-outline-variant px-6 py-12 text-center']) }}>
    <span class="material-symbols-outlined text-4xl text-on-surface-variant/50">{{ $icon }}</span>

    <p class="mt-3 font-headline-md text-base font-semibold text-on-surface">{{ $title }}</p>

    @if ($description)
        <p class="mx-auto mt-1 max-w-md text-sm text-on-surface-variant">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
            {{ $actionLabel }}
            <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
    @endif

    {{ $slot }}
</div>
