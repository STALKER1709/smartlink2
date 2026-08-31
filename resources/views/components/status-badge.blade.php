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

    /*
     * Chaque statut porte sa propre couleur : sur la liste des demandes, la
     * pastille doit dire d'un coup d'œil ce qui attend une réponse, ce qui est
     * engagé et ce qui est clos. Regrouper « Envoyée », « Vue », « Acceptée »
     * et « Terminée » sous une même teinte vide la pastille de son sens — le
     * libellé seul ne se lit pas en balayant une liste.
     *
     * Trois familles, et une seule teinte par famille à deux intensités : le
     * jaune pour ce qui attend, le vert pour ce qui avance, le rouge pour ce
     * qui a échoué. La table employait auparavant le `tertiary`, qui portait
     * l'ambre avant la bascule sur la charte du drapeau et porte le rouge
     * depuis — « Envoyée » se retrouvait en #ffdad7 quand « Refusée » était en
     * #ffdad6, soit la même pastille à un point près, et « En cours »
     * s'affichait en rouge plein.
     */
    $colors = [
        // Ce qui attend une action du prestataire. « Envoyée » crie, « Vue »
        // murmure : la demande est arrivée, elle attend toujours.
        'draft' => 'bg-surface-container-high text-on-surface-variant',
        'sent' => 'bg-secondary-container text-on-secondary-container',
        'viewed' => 'bg-secondary-fixed text-on-secondary-fixed-variant',

        // Engagé : le vert monte avec l'avancement.
        'accepted' => 'bg-primary-container/20 text-primary',
        'in_progress' => 'bg-primary-container/40 text-primary',

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
        ? 'rounded-full px-2 py-1 font-label-sm text-label-sm uppercase tracking-wider'
        : 'rounded-full px-3 py-1 font-button-text text-label-sm font-semibold';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center '.$forme.' '.($colors[$status] ?? 'bg-surface-container-high text-on-surface-variant')]) }}>
    {{ $labels[$status] ?? $status }}
</span>
