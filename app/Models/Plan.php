<?php

namespace App\Models;

use App\Services\Ai\SmartLinkContext;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'price_xaf', 'max_services', 'max_monthly_requests',
    'is_featured', 'has_ai_writing', 'has_stats', 'is_active', 'sort_order',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    public const CODE_ESSENTIAL = 'essential';

    public const CODE_PRO = 'pro';

    protected static function booted(): void
    {
        // Le contexte de l'assistant énonce les paliers et les catégories :
        // il doit être reconstruit dès que l'un d'eux change.
        static::saved(fn () => app(SmartLinkContext::class)->forget());
        static::deleted(fn () => app(SmartLinkContext::class)->forget());
    }

    protected function casts(): array
    {
        return [
            'price_xaf' => 'integer',
            'max_services' => 'integer',
            'max_monthly_requests' => 'integer',
            'is_featured' => 'boolean',
            'has_ai_writing' => 'boolean',
            'has_stats' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Le palier offert pendant l'essai gratuit. */
    public static function trialPlan(): ?self
    {
        return static::query()->where('code', self::CODE_PRO)->first();
    }

    public function name(): string
    {
        return __('ui.plans.'.$this->code.'.name');
    }

    public function tagline(): string
    {
        return __('ui.plans.'.$this->code.'.tagline');
    }

    public function formattedPrice(): string
    {
        return number_format($this->price_xaf, 0, ',', ' ').' FCFA';
    }

    public function allowsUnlimitedServices(): bool
    {
        return $this->max_services === null;
    }

    public function allowsUnlimitedRequests(): bool
    {
        return $this->max_monthly_requests === null;
    }
}
