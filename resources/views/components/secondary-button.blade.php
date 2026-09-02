@props(['href' => null])

{{-- Voir primary-button : même raison d'être polymorphe. --}}
@php
    $classes = 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-surface-container-lowest border border-primary rounded-full font-button-text text-button-text text-primary hover:bg-primary-container/10 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-colors duration-150';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
