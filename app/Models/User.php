<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_CLIENT = 'client';

    public const ROLE_PROVIDER = 'provider';

    public const ROLE_ADMIN = 'admin';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function markPhoneAsVerified(): void
    {
        $this->forceFill(['phone_verified_at' => now()])->save();
    }

    public function preferredLocale(): string
    {
        return $this->locale ?? 'fr';
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'provider_id');
    }

    /**
     * Les prestataires mis en favori. Une table pivot plutôt qu'une colonne
     * JSON : on cherche autant « qui a mis ce prestataire en favori » que
     * l'inverse, et une contrainte d'unicité vaut mieux qu'un dédoublonnage
     * à l'écriture.
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(ProviderProfile::class, 'favorites')
            ->withTimestamps();
    }

    public function sentRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'client_id');
    }

    public function receivedRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'provider_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'client_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    public function conversationsAsClient(): HasMany
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    public function conversationsAsProvider(): HasMany
    {
        return $this->hasMany(Conversation::class, 'provider_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * L'abonnement qui ouvre les droits en ce moment : essai ou payé, non échu.
     * Renvoie null dès l'expiration — c'est ce qui masque les services.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->usable()
            ->with('plan')
            ->latest('ends_at')
            ->first();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    public function hasUsableSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isProvider(): bool
    {
        return $this->role === self::ROLE_PROVIDER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function scopeOfRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
