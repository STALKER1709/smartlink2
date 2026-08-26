{{-- Une ligne de liste d'administration : un filet en bas, rien autour. --}}
<div {{ $attributes->merge(['class' => 'border-b border-outline-variant py-4']) }}>
    {{ $slot }}
</div>
