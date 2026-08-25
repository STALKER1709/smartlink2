<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProvider;
use App\Contracts\WebhookEvent;
use App\Services\Payment\HrSkills\HrSkillsCore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HR-Skills Pay — encaissement Mobile Money (MTN, Orange) au Cameroun.
 *
 * Authentification à double clé : la clé A voyage dans « Authorization:
 * Bearer », la clé B est échangée contre un token de transaction valable
 * 45 minutes, réclamé par l'en-tête « X-Transaction-Token ». La clé B
 * accompagne aussi chaque appel dans « X-API-Secret » : la documentation
 * décrit l'échange de token dans ses exemples complets, mais publie par
 * ailleurs un « 401 missing_api_secret » réclamant ce second en-tête. Les
 * deux sont envoyés, aucun n'étant de trop.
 *
 * Les chemins mélangent « /v1/… » et « /api/v1/… » : c'est ainsi que la
 * documentation les publie, on les reproduit tels quels.
 */
class HrSkillsPayProvider implements PaymentProvider
{
    private const TOKEN_CACHE_KEY = 'hrskills.transaction_token';

    /** Marge avant expiration réelle : évite d'utiliser un token qui meurt en vol. */
    private const RENEWAL_MARGIN_SECONDS = 300;

    public function collect(
        string $phone,
        string $operator,
        int $amountXaf,
        string $description,
        string $reference,
    ): CollectionResult {
        $operator = HrSkillsCore::normalizeOperator($operator);

        if (! HrSkillsCore::isValidOperator($operator)) {
            return CollectionResult::failed("Opérateur inconnu : {$operator}");
        }

        $normalizedPhone = HrSkillsCore::normalizePhone($phone);

        if ($normalizedPhone === null) {
            return CollectionResult::failed('Numéro de téléphone inexploitable.');
        }

        try {
            $response = Http::withHeaders($this->headers(HrSkillsCore::idempotencyKey($reference)))
                ->post($this->apiRoot().'/api/v1/payin/mobile-money', [
                    'operator' => $operator,
                    'country' => 'CM',
                    'phone_number' => $normalizedPhone,
                    'amount' => $amountXaf,
                    'currency' => 'XAF',
                    'description' => $description,
                    // Nous revient tel quel dans le rappel : permet de
                    // retrouver notre paiement même si la correspondance par
                    // référence fournisseur échouait.
                    'metadata' => ['reference_interne' => $reference],
                ]);

            if (! $response->successful()) {
                Log::warning('[HR-Skills] Encaissement refusé', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return CollectionResult::failed('Encaissement refusé par l\'opérateur.');
            }

            $providerReference = $response->json('data.reference');

            if (! is_string($providerReference) || $providerReference === '') {
                return CollectionResult::failed('Réponse HR-Skills sans référence.');
            }

            // Aucune redirection : le payeur valide sur son téléphone.
            return CollectionResult::pending($providerReference);
        } catch (\Throwable $e) {
            Log::error('[HR-Skills] Encaissement en échec', ['error' => $e->getMessage()]);

            return CollectionResult::failed($e->getMessage());
        }
    }

    public function status(string $providerReference): ?string
    {
        $raw = $this->rawStatus($providerReference);

        return $raw === null ? null : HrSkillsCore::mapStatus($raw);
    }

    public function isAuthentic(Request $request): bool
    {
        $secret = (string) config('payment.hrskills.webhook_secret');

        if ($secret === '') {
            // Sans secret, aucune signature n'est vérifiable : on refuse tout
            // plutôt que d'ouvrir la porte à qui connaît une référence.
            Log::error('[HR-Skills] Rappel refusé : secret de rappel non configuré.');

            return false;
        }

        $authentique = HrSkillsCore::verifySignature($secret, $request->getContent(), [
            $request->header('X-Hub-Signature'),
            $request->header('X-Hub-Signature-256'),
        ]);

        if (! $authentique) {
            Log::warning('[HR-Skills] Signature de rappel invalide', ['ip' => $request->ip()]);
        }

        return $authentique;
    }

    public function readWebhook(Request $request): ?WebhookEvent
    {
        $raw = $request->getContent();

        // Contenu : structure non documentée, lecture tolérante.
        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            // Signature valide mais corps inanalysable : décrit sans ses
            // valeurs, c'est ce qui distingue un simple test du tableau de
            // bord d'un vrai événement encodé autrement qu'en JSON.
            Log::error('[HR-Skills] Rappel authentique mais corps non-JSON · '
                .HrSkillsCore::bodyDiagnostic($raw, (string) $request->header('Content-Type')));

            return null;
        }

        $read = HrSkillsCore::readWebhookPayload($payload);

        if ($read === null) {
            // Authentique mais sans rapport avec un paiement : un test depuis
            // leur console, ou un événement dont nous n'avons que faire. La
            // structure est journalisée — clés seules, jamais les valeurs —
            // parce que c'est la seule façon d'apprendre comment le
            // fournisseur nomme ses champs, sa documentation n'en publiant
            // aucun exemple. Le nom de l'événement, lui, est utile en clair.
            Log::info('[HR-Skills] Rappel authentique sans paiement exploitable · événement: '
                .(is_string($payload['event'] ?? null) ? $payload['event'] : 'non nommé')
                .' · structure reçue: '.HrSkillsCore::jsonShape($payload));

            return null;
        }

        return new WebhookEvent(
            providerReference: $read['reference'],
            internalReference: $read['internal_reference'],
        );
    }

    /** Statut brut, tel que renvoyé par le fournisseur. */
    private function rawStatus(string $providerReference): ?string
    {
        try {
            $response = Http::withHeaders($this->headers(null))
                ->get($this->apiRoot().'/v1/payments/'.rawurlencode($providerReference));

            if (! $response->successful()) {
                Log::warning('[HR-Skills] Lecture de statut refusée', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $status = $response->json('data.status') ?? $response->json('status');

            return is_string($status) && $status !== '' ? $status : null;
        } catch (\Throwable $e) {
            Log::error('[HR-Skills] Lecture de statut en échec', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(?string $idempotencyKey): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->keyA(),
            'X-Transaction-Token' => $this->transactionToken(),
            'X-API-Secret' => $this->keyB(),
            'Accept' => 'application/json',
        ];

        // La documentation ne rend « Idempotency-Key » obligatoire que sur
        // les POST.
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $headers;
    }

    /** Token de transaction, renouvelé seulement quand il approche de sa fin. */
    private function transactionToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::withHeaders(['Authorization' => 'Bearer '.$this->keyA()])
            ->post($this->baseUrl().'/v1/auth/transaction-token', [
                'api_secret' => $this->keyB(),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'HR-Skills auth : HTTP '.$response->status().' · '.mb_substr($response->body(), 0, 200)
            );
        }

        $token = $response->json('transaction_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('HR-Skills auth : token absent de la réponse.');
        }

        // « expires_in » est en secondes (2700 d'après la documentation).
        $lifetime = (int) ($response->json('expires_in') ?? 2700);
        Cache::put(self::TOKEN_CACHE_KEY, $token, max($lifetime - self::RENEWAL_MARGIN_SECONDS, 60));

        return $token;
    }

    private function keyA(): string
    {
        $key = (string) config('payment.hrskills.key_a');

        if ($key === '') {
            throw new \RuntimeException('HRSKILLS_CLE_A manquant.');
        }

        return $key;
    }

    private function keyB(): string
    {
        $key = (string) config('payment.hrskills.key_b');

        if ($key === '') {
            throw new \RuntimeException('HRSKILLS_CLE_B manquant.');
        }

        return $key;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('payment.hrskills.base_url'), '/');
    }

    /** Racine des appels qui consomment le token : « /sandbox » en test. */
    private function apiRoot(): string
    {
        return HrSkillsCore::apiRoot($this->baseUrl(), $this->keyA());
    }
}
