@props(['status'])

@php
    /*
     * La puce d'état des fiches, d'après les maquettes : fond neutre, un point
     * de couleur, le libellé. Elle se distingue de la pastille de liste
     * (`x-status-badge`) : sur une fiche il n'y a qu'un état à montrer, et il
     * doit se lire sans que la couleur crie.
     */
    $points = [
        \App\Support\RequestStatus::DRAFT => 'bg-outline',
        \App\Support\RequestStatus::SENT => 'bg-tertiary',
        \App\Support\RequestStatus::VIEWED => 'bg-secondary',
        \App\Support\RequestStatus::ACCEPTED => 'bg-secondary',
        \App\Support\RequestStatus::IN_PROGRESS => 'bg-tertiary',
        \App\Support\RequestStatus::COMPLETED => 'bg-primary',
        \App\Support\RequestStatus::REFUSED => 'bg-error',
        \App\Support\RequestStatus::CANCELLED => 'bg-outline',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-full bg-surface-container-high px-4 py-2 font-button-text text-button-text text-on-surface']) }}>
    <span class="h-2 w-2 shrink-0 rounded-full {{ $points[$status] ?? 'bg-outline' }}" aria-hidden="true"></span>
    {{ \App\Support\RequestStatus::label($status) }}
</span>
