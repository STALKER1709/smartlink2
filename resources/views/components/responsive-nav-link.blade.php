@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex min-h-12 w-full items-center ps-3 pe-4 border-l-4 border-primary text-start text-body-md font-semibold text-primary bg-primary-container/15 focus:outline-none transition duration-150 ease-in-out'
            : 'flex min-h-12 w-full items-center ps-3 pe-4 border-l-4 border-transparent text-start text-body-md font-medium text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
