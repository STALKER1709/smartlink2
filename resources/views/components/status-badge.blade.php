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
        'draft' => 'bg-gray-100 text-gray-700',
        'sent' => 'bg-blue-100 text-blue-700',
        'viewed' => 'bg-violet-100 text-violet-700',
        'accepted' => 'bg-teal-100 text-teal-700',
        'refused' => 'bg-red-100 text-red-700',
        'in_progress' => 'bg-amber-100 text-amber-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-gray-200 text-gray-600',
        'active' => 'bg-green-100 text-green-700',
        'inactive' => 'bg-gray-200 text-gray-600',
        'suspended' => 'bg-red-100 text-red-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.($colors[$status] ?? 'bg-gray-100 text-gray-700')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
