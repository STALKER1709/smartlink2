<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_id', 'payer_id', 'amount_xaf', 'operator', 'phone',
    'type', 'status', 'campay_reference', 'internal_reference',
    'failure_reason', 'paid_at',
])]
class Payment extends Model
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_FINAL = 'final';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_xaf' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount_xaf, 0, ',', ' ').' FCFA';
    }
}
