<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trial_that_has_not_lapsed_opens_rights(): void
    {
        $subscription = Subscription::factory()->trialing()->create();

        $this->assertTrue($subscription->isUsable());
        $this->assertTrue($subscription->isTrial());
    }

    public function test_a_paid_subscription_that_has_not_lapsed_opens_rights(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertTrue($subscription->isUsable());
        $this->assertFalse($subscription->isTrial());
    }

    public function test_a_subscription_past_its_due_date_opens_nothing(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse($subscription->isUsable());
        $this->assertSame(0, $subscription->daysRemaining());
    }

    /**
     * `isUsable()` fonctionne par liste blanche : seuls « trialing » et
     * « active » ouvrent des droits.
     *
     * Le statut « cancelled » n'existe plus côté code — aucun chemin n'y menait
     * — mais la contrainte de la table l'autorise toujours, et une ligne
     * antérieure peut le porter. Rétrécir l'énumération ferait échouer la
     * migration sur cette donnée-là ; la garantie tenable est donc celle-ci :
     * un statut que le code ne connaît pas n'ouvre rien, même avec une
     * échéance dans le futur.
     */
    public function test_a_status_the_code_no_longer_knows_opens_nothing(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'cancelled',
            'ends_at' => now()->addDays(10),
        ]);

        $this->assertFalse($subscription->isUsable());
    }

    public function test_a_provider_reads_its_rights_from_the_current_subscription(): void
    {
        $provider = User::factory()->provider()->create();
        $pro = Plan::factory()->pro()->create();

        $this->assertFalse($provider->hasUsableSubscription());
        $this->assertNull($provider->currentPlan());

        Subscription::factory()->trialing()->create([
            'user_id' => $provider->id,
            'plan_id' => $pro->id,
        ]);

        // L'abonnement est mémoïsé le temps d'une requête : il vient d'être
        // créé en dehors de cette instance, qui porte encore sa réponse
        // précédente.
        $provider->refresh();

        $this->assertTrue($provider->hasUsableSubscription());
        $this->assertSame(Plan::CODE_PRO, $provider->currentPlan()->code);
    }

    public function test_an_expired_provider_keeps_the_account_but_loses_the_plan(): void
    {
        $provider = User::factory()->provider()->create();
        Subscription::factory()->expired()->create(['user_id' => $provider->id]);

        $this->assertFalse($provider->hasUsableSubscription());
        $this->assertNull($provider->currentPlan());
        $this->assertTrue($provider->isActive());
    }

    public function test_the_usable_scope_only_returns_subscriptions_that_open_rights(): void
    {
        Subscription::factory()->trialing()->create();
        Subscription::factory()->create();
        Subscription::factory()->expired()->create();

        // Même garantie côté requête que côté modèle : la portée filtre sur la
        // liste blanche, donc une ligne héritée au statut « cancelled » reste
        // dehors sans qu'on ait à tenir une liste noire.
        Subscription::factory()->create([
            'status' => 'cancelled',
            'ends_at' => now()->addDays(5),
        ]);

        $this->assertSame(2, Subscription::query()->usable()->count());
    }
}
