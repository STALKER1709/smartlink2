<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampayService
{
    private ?string $token = null;

    private function baseUrl(): string
    {
        return rtrim(config('campay.base_url'), '/');
    }

    private function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $response = Http::post($this->baseUrl().'/token/', [
            'username' => config('campay.username'),
            'password' => config('campay.password'),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Campay authentication failed: '.$response->body());
        }

        $this->token = $response->json('token');

        return $this->token;
    }

    /**
     * Demande de collecte Mobile Money. Campay ne propose ni mandat récurrent
     * ni prélèvement automatique : le payeur valide chaque opération sur son
     * téléphone. L'appel renvoie donc presque toujours PENDING, et c'est le
     * rappel de l'opérateur qui confirme.
     *
     * @return array{reference: ?string, status: string, ussd_code?: ?string, operator?: ?string, error?: string}
     */
    public function collect(string $phone, int $amountXaf, string $description, string $externalRef): array
    {
        if ($this->isSandboxWithoutCredentials()) {
            $simulatedRef = 'MOCK_'.strtoupper(Str::random(10));
            Log::info('[Campay][mock] collect', compact('phone', 'amountXaf', 'description', 'externalRef', 'simulatedRef'));

            return ['reference' => $simulatedRef, 'status' => 'SUCCESSFUL', 'operator' => 'MTN'];
        }

        try {
            $token = $this->getToken();

            $payload = [
                'amount' => (string) $amountXaf,
                'from' => $this->normalizePhone($phone),
                'description' => $description,
                'external_reference' => $externalRef,
            ];

            if (config('campay.callback_url')) {
                $payload['callback_url'] = config('campay.callback_url');
            }

            $response = Http::withToken($token)
                ->post($this->baseUrl().'/collect/', $payload);

            if (! $response->successful()) {
                Log::warning('[Campay] collect failed', ['body' => $response->body()]);

                return ['reference' => null, 'status' => 'FAILED', 'error' => $response->body()];
            }

            return $this->normalizeCollectResponse($response->json());
        } catch (\Throwable $e) {
            Log::error('[Campay] Exception', ['error' => $e->getMessage()]);

            return ['reference' => null, 'status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }

    /**
     * Une collecte acceptée renvoie une référence, sans champ de statut : la
     * traiter comme un échec ferait échouer tout paiement réel. Sans référence
     * en revanche, rien n'a été engagé.
     *
     * @param  array<string, mixed>|null  $body
     * @return array{reference: ?string, status: string, ussd_code?: ?string, operator?: ?string, error?: string}
     */
    private function normalizeCollectResponse(?array $body): array
    {
        $reference = $body['reference'] ?? null;

        if ($reference === null) {
            return ['reference' => null, 'status' => 'FAILED', 'error' => 'Réponse Campay sans référence.'];
        }

        $status = strtoupper((string) ($body['status'] ?? 'PENDING'));

        return [
            'reference' => $reference,
            'status' => in_array($status, ['SUCCESSFUL', 'FAILED', 'CANCELLED'], true) ? $status : 'PENDING',
            'ussd_code' => $body['ussd_code'] ?? null,
            'operator' => $body['operator'] ?? null,
        ];
    }

    public function getTransactionStatus(string $reference): array
    {
        if ($this->isSandboxWithoutCredentials()) {
            return ['status' => 'SUCCESSFUL', 'reference' => $reference];
        }

        try {
            $token = $this->getToken();
            $response = Http::withToken($token)->get($this->baseUrl().'/transaction/'.$reference.'/');

            return $response->successful() ? $response->json() : ['status' => 'FAILED'];
        } catch (\Throwable $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($digits, '237')) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return '237'.$digits;
        }

        return $digits;
    }

    private function isSandboxWithoutCredentials(): bool
    {
        return config('campay.sandbox') && empty(config('campay.username'));
    }
}
