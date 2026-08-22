<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'plan_id', 'status', 'starts_at', 'ends_at', 'cancelled_at'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Un abonnement donne accès tant qu'il n'est ni expiré ni échu.
     * L'essai gratuit ouvre exactement les mêmes droits que le palier qu'il porte.
     */
    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_TRIALING, self::STATUS_ACTIVE], true)
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIALING;
    }

    public function daysRemaining(): int
    {
        if ($this->ends_at === null || $this->ends_at->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($this->ends_at, absolute: true));
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_TRIALING, self::STATUS_ACTIVE])
            ->where('ends_at', '>', now());
    }
}
