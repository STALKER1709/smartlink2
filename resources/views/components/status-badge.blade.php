@props(['status', 'variant' => 'pill'])

@php
    // Les libellés des statuts de demande viennent de `RequestStatus` : la
    // table était recopiée ici, dans le filtre de la liste des demandes et
    // dans le tableau de bord d'administration.
    $labels = \App\Support\RequestStatus::labels() + [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'suspended' => 'Suspendu',
    ];

    // Chaque statut porte sa propre couleur : sur la liste des demandes, la
    // pastille doit dire d'un coup d'œil ce qui attend une réponse, ce qui est
    // engagé et ce qui est clos. Regrouper « Envoyée », « Vue », « Acceptée »
    // et « Terminée » sous une même teinte vide la pastille de son sens — le
    // libellé seul ne se lit pas en balayant une liste.
    $colors = [
        // En attente d'une action du prestataire : teinte chaude, distincte
        // de tous les verts.
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'sent' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',
        'viewed' => 'bg-primary-container/20 text-primary',

        // Engagé.
        'accepted' => 'bg-secondary-fixed-dim text-on-secondary-fixed-variant',
        'in_progress' => 'bg-tertiary-container text-on-tertiary-container',

        // Clos.
        'completed' => 'bg-primary-container text-on-primary-container',
        'refused' => 'bg-error-container text-on-error-container',
        // Le barré encode l'abandon par la forme, ce qui distingue « Annulée »
        // de « Brouillon » sans ajouter une huitième teinte.
        'cancelled' => 'bg-surface-container-high text-on-surface-variant line-through',

        // États de service et de compte.
        'active' => 'bg-primary-container/20 text-primary',
        'inactive' => 'bg-surface-container-high text-on-surface-variant',
        'suspended' => 'bg-error-container text-on-error-container',
    ];
@endphp

{{-- Deux formes, toutes deux issues des maquettes : la pastille arrondie des
     tableaux de bord, et l'étiquette carrée en capitales des listes de
     demandes. --}}
@php
    $forme = $variant === 'caps'
        ? 'rounded-sm px-2 py-1 font-label-sm text-label-sm uppercase tracking-wider'
        : 'rounded-full px-3 py-1 font-button-text text-label-sm font-semibold';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center '.$forme.' '.($colors[$status] ?? 'bg-surface-container-high text-on-surface-variant')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
