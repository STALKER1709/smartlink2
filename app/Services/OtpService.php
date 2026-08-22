<?php

namespace App\Services;

use App\Models\OtpCode;

class OtpService
{
    private const EXPIRY_MINUTES = 10;

    private const MAX_ATTEMPTS = 3;

    public function __construct(private readonly SmsService $sms) {}

    public function send(string $phone, string $purpose = 'phone_verification'): bool
    {
        OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        OtpCode::create([
            'phone' => $phone,
            'code' => hash('sha256', $code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $message = __('sms.otp', ['code' => $code, 'minutes' => self::EXPIRY_MINUTES]);

        return $this->sms->send($phone, $message);
    }

    public function verify(string $phone, string $code, string $purpose = 'phone_verification'): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->delete();

            return false;
        }

        if (! hash_equals($otp->code, hash('sha256', $code))) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }
}
