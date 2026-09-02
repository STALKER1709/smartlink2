@php
    $cible = 'inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-outline-variant px-4 font-label-md text-label-md transition-colors';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination') }}" class="flex items-center justify-between gap-4">
        @if ($paginator->onFirstPage())
            <span class="{{ $cible }} cursor-default border-transparent text-outline" aria-disabled="true">
                <x-icon name="chevron_left" size="sm" />
                {{ __('Précédent') }}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="{{ $cible }} text-on-surface hover:bg-surface-container">
                <x-icon name="chevron_left" size="sm" />
                {{ __('Précédent') }}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="{{ $cible }} text-on-surface hover:bg-surface-container">
                {{ __('Suivant') }}
                <x-icon name="chevron_right" size="sm" />
            </a>
        @else
            <span class="{{ $cible }} cursor-default border-transparent text-outline" aria-disabled="true">
                {{ __('Suivant') }}
                <x-icon name="chevron_right" size="sm" />
            </span>
        @endif
    </nav>
@endif
