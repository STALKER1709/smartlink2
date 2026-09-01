{{-- La maintenance est le seul cas où la page d'erreur est attendue : on l'a
     déclenchée soi-même. Elle sert pendant qu'on répare, donc au moment précis
     où le reste de l'application peut être à moitié en place — autonome, elle
     aussi. Voir errors/partials/autonome. --}}
@include('errors.partials.autonome', [
    'titre' => __('Maintenance'),
    'intitule' => __('SmartLink revient dans quelques minutes'),
    'texte' => __("Nous remettons le site en service. Rien n'est perdu : vos demandes, vos messages et votre abonnement sont intacts."),
    // Une horloge : ce qui manque ici est du temps, pas une réparation du
    // côté du visiteur.
    'dessin' => '<svg width="56" height="56" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="16" cy="16" r="12.5" stroke="currentColor" stroke-width="3"/>
        <path d="M16 9v7.5l5 3" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>',
])
