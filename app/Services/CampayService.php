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

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('[Campay] Exception', ['error' => $e->getMessage()]);
            return ['reference' => null, 'status' => 'FAILED', 'error' => $e->getMessage()];
        }
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
