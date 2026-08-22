<?php

namespace App\Services\Payment;

/**
 * Issue d'une demande d'encaissement.
 */
class CollectionResult
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $providerReference = null,
        public readonly ?string $error = null,
    ) {}

    public static function pending(string $providerReference): self
    {
        return new self(self::STATUS_PENDING, $providerReference);
    }

    public static function success(string $providerReference): self
    {
        return new self(self::STATUS_SUCCESS, $providerReference);
    }

    public static function failed(string $error, ?string $providerReference = null): self
    {
        return new self(self::STATUS_FAILED, $providerReference, $error);
    }
}
