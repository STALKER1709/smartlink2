<?php

return [
    /*
     * Fournisseur d'encaissement Mobile Money.
     * « mock » n'appelle aucune API : mode par défaut, pour développer et
     * tester sans compte ni réseau.
     */
    'driver' => env('PAYMENT_PROVIDER', 'mock'),

    'hrskills' => [
        /*
         * Authentification à double clé. Les deux clés doivent porter le même
         * environnement — soit les deux en _test_, soit les deux en _live_ :
         * l'application choisit son chemin d'après la clé A, et un mélange
         * enverrait des appels de production authentifiés par un secret de
         * test. La commande `payment:check` le vérifie.
         */
        'key_a' => env('HRSKILLS_CLE_A'),
        'key_b' => env('HRSKILLS_CLE_B'),

        /*
         * Sans ce secret, la signature des rappels ne peut pas être vérifiée
         * et n'importe qui pourrait déclarer un abonnement réglé. Le rappel se
         * ferme plutôt que de s'ouvrir à tous.
         */
        'webhook_secret' => env('HRSKILLS_WEBHOOK_SECRET', ''),

        'base_url' => env('HRSKILLS_BASE_URL', 'https://api.hrskills-pay.com'),
    ],
];
