@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-outline-variant text-on-surface placeholder-on-surface-variant/60 focus:border-primary focus:ring-primary rounded-lg disabled:bg-surface-container-low']) }}>
