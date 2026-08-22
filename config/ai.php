<?php

return [
    /*
     * Pilote de l'assistant. « rule » n'appelle aucune API et ne coûte rien :
     * c'est le mode par défaut, et le filet vers lequel tout garde-fou rabat.
     */
    'driver' => env('AI_DRIVER', 'rule'),

    'api_key' => env('ANTHROPIC_API_KEY'),

    /*
     * Un modèle par tâche : le plus capable pour ce qui est lu par un humain,
     * le plus économique pour l'extraction et le classement en volume.
     */
    'models' => [
        'chat' => env('AI_MODEL_CHAT', 'claude-opus-5'),
        'writing' => env('AI_MODEL_WRITING', 'claude-opus-5'),
        'search' => env('AI_MODEL_SEARCH', 'claude-haiku-4-5'),
        'moderation' => env('AI_MODEL_MODERATION', 'claude-haiku-4-5'),
    ],

    /*
     * Tarifs en dollars par million de jetons, servant au calcul du coût
     * enregistré dans ai_usages et au plafond mensuel.
     */
    'pricing' => [
        'claude-opus-5' => ['input' => 5.00, 'output' => 25.00],
        'claude-haiku-4-5' => ['input' => 1.00, 'output' => 5.00],
    ],

    'limits' => [
        /*
         * Les visiteurs anonymes restent sur le mode par règles : l'IA est
         * un motif d'inscription, pas une dépense ouverte à tout venant.
         */
        'require_authentication' => (bool) env('AI_REQUIRE_AUTH', true),

        /* Messages d'assistant par compte et par jour. */
        'daily_messages_per_user' => (int) env('AI_DAILY_MESSAGES', 20),

        /*
         * Plafond de dépense mensuel, en dollars. Au-delà, toute la plateforme
         * bascule en mode par règles et l'administration est alertée.
         */
        'monthly_budget_usd' => (float) env('AI_MONTHLY_BUDGET_USD', 50),
    ],

    /* Nombre de messages d'historique renvoyés au modèle à chaque tour. */
    'history_turns' => (int) env('AI_HISTORY_TURNS', 6),
];
