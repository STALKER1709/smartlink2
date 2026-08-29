@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-label-lg text-secondary']) }}>
        {{ $status }}
    </div>
@endif
