<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Secret des tâches planifiées déclenchées par HTTP
    |--------------------------------------------------------------------------
    |
    | Sur un hébergement serverless, aucun processus ne tourne en continu :
    | `php artisan schedule:run` n'a nulle part où vivre. Le planificateur de
    | l'hébergeur appelle alors une URL. Cette URL doit être protégée, sinon
    | n'importe qui peut déclencher le passage quotidien à volonté.
    |
    | Vercel Cron envoie automatiquement l'en-tête « Authorization: Bearer
    | <CRON_SECRET> » dès que la variable CRON_SECRET existe dans le projet.
    |
    | Sans secret configuré, la route répond 503 : elle refuse plutôt que de
    | s'ouvrir à tous — même choix que pour la signature des rappels de
    | paiement.
    |
    */

    'secret' => env('CRON_SECRET'),

];
