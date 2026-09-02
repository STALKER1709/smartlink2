<?php

return [
    /*
     * Durée de l'essai gratuit ouvert à l'inscription d'un prestataire.
     * L'essai porte les droits du palier le plus complet.
     */
    'trial_days' => (int) env_or('SUBSCRIPTION_TRIAL_DAYS', 30),

    /*
     * Durée d'un cycle payé. Le Mobile Money n'autorisant pas le prélèvement
     * automatique, chaque cycle est reconduit par une validation du prestataire
     * sur son téléphone, annoncée par SMS avant l'échéance.
     */
    'cycle_days' => (int) env_or('SUBSCRIPTION_CYCLE_DAYS', 30),

    /*
     * Jours avant échéance auxquels partent les relances SMS.
     */
    'reminder_days' => [3, 1],
];
