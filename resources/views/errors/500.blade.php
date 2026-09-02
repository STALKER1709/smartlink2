{{-- Autonome par construction : voir errors/partials/autonome. --}}
@include('errors.partials.autonome', [
    'titre' => __('Erreur serveur'),
    'intitule' => __("Quelque chose s'est cassé de notre côté"),
    'texte' => __("Ce n'est pas vous. L'incident est enregistré. Réessayez dans un instant : la plupart de ces erreurs ne durent pas."),
    // Un triangle d'alerte, dans le vert du site : ce n'est pas une faute du
    // visiteur, la page n'a pas à le lui faire craindre.
    'dessin' => '<svg width="56" height="56" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M16 4.5 29 27H3L16 4.5Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
        <path d="M16 13v6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        <circle cx="16" cy="23" r="1.6" fill="currentColor"/>
    </svg>',
])
