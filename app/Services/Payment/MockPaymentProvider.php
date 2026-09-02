<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Contracts\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Encaissement simulé, pour développer et tester sans compte ni réseau.
 *
 * Il reproduit la convention de bac à sable de HR-Skills : un montant PAIR
 * aboutit, un montant IMPAIR échoue. Les paliers étant à 2 500 et 7 500 FCFA,
 * le parcours normal réussit ; forcer un échec ne demande qu'un prix impair.
 */
class MockPaymentProvider implements PaymentProvider
{
    public function collect(
        string $phone,
        string $operator,
        int $amountXaf,
        string $description,
        string $reference,
    ): CollectionResult {
        $providerReference = 'MOCK_'.strtoupper(Str::random(10));

        Log::info('[Paiement][simulation] encaissement', compact(
            'phone', 'operator', 'amountXaf', 'reference', 'providerReference',
        ));

        return $amountXaf % 2 === 0
            ? CollectionResult::success($providerReference)
            : CollectionResult::failed('Montant impair : échec simulé.', $providerReference);
    }

    public function status(string $providerReference): ?string
    {
        return 'success';
    }

    /**
     * Rien à authentifier hors ligne : la simulation n'a pas de secret partagé.
     * Le pilote « mock » n'est jamais celui de la production, où le contrôle de
     * signature appartient au vrai fournisseur.
     */
    public function isAuthentic(Request $request): bool
    {
        return true;
    }

    public function readWebhook(Request $request): ?WebhookEvent
    {
        $reference = $request->input('external_reference') ?? $request->input('reference');

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        return new WebhookEvent(providerReference: $reference, internalReference: $reference);
    }
}
