@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-label-md text-secondary']) }}>
        {{ $status }}
    </div>
@endif
