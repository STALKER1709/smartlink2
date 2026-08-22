<?php

namespace App\Contracts;

/**
 * Ce qu'un rappel authentifié nous apprend. Le statut annoncé n'y figure pas
 * volontairement : un corps signé prouve l'origine du message, pas l'état
 * courant de la transaction, qui est toujours relu chez le fournisseur.
 */
class WebhookEvent
{
    public function __construct(
        public readonly string $providerReference,
        public readonly ?string $internalReference = null,
    ) {}
}
