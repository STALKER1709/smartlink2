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
     * Le rappel vient-il bien du fournisseur ?
     *
     * Séparé de la lecture parce que les deux questions appellent des réponses
     * HTTP opposées : un rappel non authentique se refuse, un rappel
     * authentique mais sans rapport avec un paiement — un test depuis leur
     * console, par exemple — s'accuse en réception. Répondre « rejeté » à
     * celui-là ferait croire au fournisseur que notre point d'entrée est en
     * panne.
     */
    public function isAuthentic(Request $request): bool;

    /**
     * Extrait ce qui est exploitable d'un rappel déjà authentifié.
     * Renvoie null quand le rappel ne concerne aucun paiement.
     */
    public function readWebhook(Request $request): ?WebhookEvent;
}
