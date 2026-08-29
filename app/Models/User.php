<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
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
class User extends Authenticatable implements HasLocalePreference
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

    /**
     * La langue dans laquelle écrire à cette personne.
     *
     * Lue par Laravel du fait de `HasLocalePreference` — sans le contrat, la
     * méthode existait sans que rien ne l'appelle jamais. Elle compte surtout
     * pour la réinitialisation de mot de passe : l'utilisateur n'est alors pas
     * connecté, la langue de la session n'existe pas, et le message partait
     * donc systématiquement dans la langue par défaut, quelle que soit celle
     * choisie par le destinataire.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.fallback_locale', 'fr');
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

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Mémo de l'abonnement en cours, valable le temps de la requête HTTP.
     *
     * Deux champs plutôt qu'un : l'absence d'abonnement est une réponse en
     * soi, et `null` seul ne dirait pas si la question a déjà été posée.
     */
    private ?Subscription $abonnementMemo = null;

    private bool $abonnementResolu = false;

    /**
     * L'abonnement qui ouvre les droits en ce moment : essai ou payé, non échu.
     * Renvoie null dès l'expiration — c'est ce qui masque les services.
     *
     * Le résultat est mémoïsé sur l'instance. Sans cela, une seule page de
     * prestataire rejouait la requête jusqu'à sept fois : la barre de
     * navigation, le bandeau d'abonnement, le bouton de publication et chaque
     * appel de `QuotaService` la refaisaient chacun pour leur compte. Sans
     * effet visible sur SQLite en local ; sur Supabase, autant d'allers-retours
     * réseau depuis une fonction serverless.
     *
     * Toute écriture sur l'abonnement doit appeler `forgetActiveSubscription()`
     * — c'est ce que fait `SubscriptionService`.
     */
    public function activeSubscription(): ?Subscription
    {
        if ($this->abonnementResolu) {
            return $this->abonnementMemo;
        }

        $this->abonnementResolu = true;

        return $this->abonnementMemo = $this->subscriptions()
            ->usable()
            ->with('plan')
            ->latest('ends_at')
            ->first();
    }

    /**
     * Oublie l'abonnement mémoïsé : le prochain appel réinterrogera la base.
     */
    public function forgetActiveSubscription(): static
    {
        $this->abonnementResolu = false;
        $this->abonnementMemo = null;

        return $this;
    }

    /**
     * `refresh()` recharge l'état depuis la base : le mémo doit tomber avec le
     * reste, sinon un appel après rechargement rendrait une valeur périmée —
     * ce que personne n'attend d'un modèle rafraîchi.
     */
    public function refresh(): static
    {
        $this->forgetActiveSubscription();

        return parent::refresh();
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
