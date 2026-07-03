<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);

        if (config('africastalking.sandbox') && empty(config('africastalking.api_key'))) {
            Log::channel('single')->info('[SMS][sandbox] To: '.$phone.' | '.$message);
            return true;
        }

        try {
            $endpoint = config('africastalking.sandbox')
                ? 'https://api.sandbox.africastalking.com/version1/messaging'
                : 'https://api.africastalking.com/version1/messaging';

            $response = Http::withHeaders([
                'apiKey' => config('africastalking.api_key'),
                'Accept' => 'application/json',
            ])->asForm()->post($endpoint, [
                'username' => config('africastalking.username'),
                'to' => $phone,
                'message' => $message,
                'from' => config('africastalking.sender_id'),
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('[SMS] Failed', ['phone' => $phone, 'body' => $response->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[SMS] Exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '237')) {
            return '+'.$digits;
        }

        if (strlen($digits) === 9) {
            return '+237'.$digits;
        }

        return '+'.$digits;
    }
}
