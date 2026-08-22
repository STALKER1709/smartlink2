<?php

namespace App\Contracts;

use App\Services\Payment\CollectionResult;
use Illuminate\Http\Request;

/**
 * Fournisseur d'encaissement Mobile Money. Le pilote choisit l'implémentation ;
 * rien dans le reste de l'application ne connaît le fournisseur retenu.
 */
interface PaymentProvider
{
    /**
     * Demande un encaissement. Le payeur valide ensuite sur son téléphone :
     * la réponse est donc « en attente » dans le cas normal.
     *
     * @param  string  $reference  notre propre référence, renvoyée dans le rappel
     */
    public function collect(
        string $phone,
        string $operator,
        int $amountXaf,
        string $description,
        string $reference,
    ): CollectionResult;

    /**
     * Statut courant chez le fournisseur, seule source de vérité.
     * Renvoie null quand rien n'est définitif ou que la lecture échoue.
     */
    public function status(string $providerReference): ?string;

    /**
     * Authentifie un rappel et en extrait ce qui est exploitable.
     */
    public function readWebhook(Request $request): ?WebhookEvent;
}
