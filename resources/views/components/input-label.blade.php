@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-body-md text-label-md font-medium text-on-surface mb-1']) }}>
    {{ $value ?? $slot }}
</label>
