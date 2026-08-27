@props(['title', 'icon' => null, 'tone' => 'neutral'])

@php
    $danger = $tone === 'danger';
@endphp

{{--
    La carte à bandeau des maquettes : un titre porté par un pictogramme, une
    filière, puis le contenu. Trois réglages du compte s'y rangent — ce qui
    vous identifie, ce qui vous protège, ce qui vous efface — et le bandeau les
    sépare à la lecture sans qu'on ait à lire.
--}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border '.($danger ? 'border-error/30 bg-error-container/20' : 'border-outline-variant bg-surface-container-lowest')]) }}>
    <div class="border-b px-5 py-4 sm:px-6 sm:py-5 {{ $danger ? 'border-error/30' : 'border-outline-variant' }}">
        <h2 class="flex items-center gap-2 font-headline-md text-headline-md text-on-surface">
            @if ($icon)
                <span class="material-symbols-outlined {{ $danger ? 'text-error' : 'text-primary' }}" aria-hidden="true">{{ $icon }}</span>
            @endif
            {{ $title }}
        </h2>
    </div>

    <div class="p-5 sm:p-6">
        {{ $slot }}
    </div>
</section>
