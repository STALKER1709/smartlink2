@props(['status'])

@php
    $labels = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'viewed' => 'Vue',
        'accepted' => 'Acceptée',
        'refused' => 'Refusée',
        'in_progress' => 'En cours',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'suspended' => 'Suspendu',
    ];

    $colors = [
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'sent' => 'bg-secondary-container text-on-secondary-container',
        'viewed' => 'bg-secondary-container text-on-secondary-container',
        'accepted' => 'bg-secondary-container text-on-secondary-container',
        'refused' => 'bg-error-container text-on-error-container',
        'in_progress' => 'bg-tertiary-container/20 text-tertiary',
        'completed' => 'bg-secondary-container text-on-secondary-container',
        'cancelled' => 'bg-surface-container-high text-on-surface-variant',
        'active' => 'bg-secondary-container text-on-secondary-container',
        'inactive' => 'bg-surface-container-high text-on-surface-variant',
        'suspended' => 'bg-error-container text-on-error-container',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 font-button-text text-xs font-semibold '.($colors[$status] ?? 'bg-surface-container-high text-on-surface-variant')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
