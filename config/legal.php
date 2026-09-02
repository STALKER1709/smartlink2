<?php

return [
    /*
     * Identité de l'éditeur.
     *
     * Ces valeurs ne peuvent pas être devinées : elles figurent sur les statuts
     * et l'immatriculation de la société. Tant qu'elles ne sont pas renseignées,
     * les pages légales affichent un avertissement visible plutôt qu'un texte
     * qui aurait l'air complet — des mentions légales inventées valent moins que
     * pas de mentions du tout.
     *
     * `env_or()` et non `env()` : une variable posée à vide sur l'hébergeur
     * rendrait une chaîne vide au lieu du défaut, et la page passerait pour
     * remplie.
     */
    'editeur' => [
        'raison_sociale' => env_or('LEGAL_RAISON_SOCIALE'),
        'forme_juridique' => env_or('LEGAL_FORME_JURIDIQUE'),
        'capital' => env_or('LEGAL_CAPITAL'),
        'rccm' => env_or('LEGAL_RCCM'),
        'niu' => env_or('LEGAL_NIU'),
        'siege' => env_or('LEGAL_SIEGE'),
        'directeur_publication' => env_or('LEGAL_DIRECTEUR_PUBLICATION'),
        'email' => env_or('LEGAL_EMAIL'),
        'telephone' => env_or('LEGAL_TELEPHONE'),
    ],

    /*
     * Date de la dernière révision du texte. À avancer à la main quand le
     * contenu change réellement — une date qui suit le déploiement ne veut
     * rien dire.
     */
    'derniere_revision' => '26 août 2026',

    /*
     * Les pages sont-elles passées devant un juriste ?
     *
     * Tant que ce drapeau est faux, chaque page porte un bandeau qui le dit.
     * Il se lève depuis l'environnement, jamais par un commit distrait.
     */
    'valide_juridiquement' => env_or('LEGAL_VALIDE', false),

    /*
     * Les tiers qui reçoivent réellement des données. Cette liste est tenue
     * depuis le code, pas depuis une intention : chaque entrée correspond à un
     * appel sortant que l'on peut montrer.
     */
    'sous_traitants' => [
        [
            'nom' => 'Supabase',
            'role' => 'Base de données et stockage des fichiers déposés',
            'donnees' => 'Toutes les données du compte, les photos et les pièces justificatives',
            'lieu' => 'Union européenne / États-Unis selon la région du projet',
        ],
        [
            'nom' => 'Vercel',
            'role' => 'Hébergement de l\'application',
            'donnees' => 'Adresse IP, journaux techniques des requêtes',
            'lieu' => 'États-Unis, avec diffusion par un réseau mondial',
        ],
        [
            'nom' => 'HR-Skills Pay',
            'role' => 'Encaissement des abonnements en Mobile Money',
            'donnees' => 'Numéro de téléphone du prestataire, opérateur, montant, référence',
            'lieu' => 'Cameroun',
        ],
        [
            'nom' => 'Anthropic',
            'role' => 'Assistant conversationnel, recherche en langage naturel, aide à la rédaction, modération automatique',
            'donnees' => 'Le texte des questions posées à l\'assistant, les phrases de recherche, les titres et descriptions de services, le texte des avis',
            'lieu' => 'États-Unis',
        ],
        [
            'nom' => 'Africa\'s Talking',
            'role' => 'Envoi des SMS de notification',
            'donnees' => 'Numéro de téléphone et contenu du message',
            'lieu' => 'Kenya',
        ],
    ],
];
