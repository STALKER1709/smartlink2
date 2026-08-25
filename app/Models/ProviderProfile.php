<?php

namespace App\Models;

use Database\Factories\ProviderProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'user_id', 'category_id', 'business_name', 'description', 'address', 'city', 'quarter',
    'latitude', 'longitude', 'whatsapp', 'service_areas', 'opening_hours',
    'logo_path', 'id_card_path', 'contact_methods',
])]
class ProviderProfile extends Model
{
    /** @use HasFactory<ProviderProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'service_areas' => 'array',
            'opening_hours' => 'array',
            'contact_methods' => 'array',
            'is_verified' => 'boolean',
            'is_listed' => 'boolean',
            'is_promoted' => 'boolean',
            'requests_read_count' => 'integer',
            'id_card_verified' => 'boolean',
            'rating_avg' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function whatsappUrl(): ?string
    {
        if (! $this->whatsapp) {
            return null;
        }
        $digits = preg_replace('/[^\d]/', '', $this->whatsapp);
        if (! str_starts_with($digits, '237')) {
            $digits = '237'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, User::class, 'id', 'payer_id', 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Les services appartiennent à l'utilisateur, pas au profil. Cette relation
     * fait le pont, pour pouvoir les compter sans charger le user au passage.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'provider_id', 'user_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Prestataires visibles dans les recherches : abonnement en cours et
     * plafond mensuel de demandes non atteint.
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_listed', true);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }
}
