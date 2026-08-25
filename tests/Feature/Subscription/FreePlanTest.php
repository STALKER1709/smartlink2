<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La formule à 0 FCFA. Elle n'existe que pour garder joignable un prestataire
 * qui n'est pas prêt à payer : tout ce qui ferait d'elle une porte de sortie
 * du modèle économique doit être fermé, et c'est ce que ces tests gardent.
 */
class FreePlanTest extends TestCase
{
    use RefreshDatabase;

    private Plan $gratuit;

    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gratuit = Plan::factory()->free()->create();
        $this->pro = Plan::factory()->pro()->create();
    }

    public function test_the_free_plan_is_the_one_with_no_price(): void
    {
        $this->assertTrue($this->gratuit->isFree());
        $this->assertFalse($this->pro->isFree());
        $this->assertSame($this->gratuit->id, Plan::freePlan()?->id);
    }

    /**
     * Le prix fait foi, pas le code : un palier payant ramené à zéro depuis
     * l'administration doit basculer du même côté, sans quoi il resterait
     * proposé au Mobile Money pour 0 FCFA.
     */
    public function test_a_paid_plan_dropped_to_zero_becomes_free(): void
    {
        $this->pro->update(['price_xaf' => 0]);

        $this->assertTrue($this->pro->fresh()->isFree());
    }

    public function test_an_expired_provider_can_switch_to_the_free_plan(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());

        $this->actingAs($provider)
            ->post(route('provider.subscription.free', $this->gratuit))
            ->assertRedirect(route('provider.subscription.show'));

        $subscription = $provider->subscriptions()->first()->fresh();
        $this->assertSame($this->gratuit->id, $subscription->plan_id);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->isUsable());
    }

    /**
     * Basculer alors qu'il reste du temps réglé détruirait ce qui a été payé,
     * sans rien rendre. Le refus protège le prestataire, pas la recette.
     */
    public function test_a_running_subscription_blocks_the_switch(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_ACTIVE, now()->addDays(20));

        $this->actingAs($provider)
            ->from(route('provider.subscription.checkout', $this->gratuit))
            ->post(route('provider.subscription.free', $this->gratuit))
            ->assertSessionHasErrors('plan');

        $this->assertSame($this->pro->id, $provider->subscriptions()->first()->fresh()->plan_id);
    }

    public function test_a_trial_still_running_blocks_the_switch_too(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_TRIALING, now()->addDays(10));

        $this->actingAs($provider)
            ->from(route('provider.subscription.checkout', $this->gratuit))
            ->post(route('provider.subscription.free', $this->gratuit))
            ->assertSessionHasErrors('plan');
    }

    public function test_a_paid_plan_cannot_be_activated_through_the_free_route(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());

        $this->actingAs($provider)
            ->post(route('provider.subscription.free', $this->pro))
            ->assertNotFound();
    }

    /**
     * Sans ce refus, un POST forgé lancerait un encaissement de 0 FCFA, que
     * l'opérateur rejette et qui laisserait un paiement en échec au compte du
     * prestataire.
     */
    public function test_the_free_plan_cannot_be_pushed_through_the_paid_checkout(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());

        $this->actingAs($provider)
            ->post(route('provider.subscription.subscribe', $this->gratuit), [
                'operator' => 'mtn',
                'phone' => '677123456',
            ])
            ->assertNotFound();
    }

    public function test_the_service_layer_refuses_to_collect_a_free_plan(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());

        $this->expectException(\InvalidArgumentException::class);

        app(SubscriptionService::class)->requestPayment($provider, $this->gratuit, '677123456', 'mtn');
    }

    /**
     * Le trou que la formule ouvrirait sans surveillance : publier dix
     * services pendant l'essai au palier Pro, puis retomber sur un palier qui
     * n'en autorise qu'un, en les gardant tous visibles.
     */
    public function test_switching_down_hides_the_services_above_the_cap(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());
        $services = $this->publishServices($provider, 4);

        $this->actingAs($provider)
            ->post(route('provider.subscription.free', $this->gratuit))
            ->assertRedirect(route('provider.subscription.show'));

        // Le plus ancien survit, arbitrairement mais de façon stable.
        $this->assertSame(Service::STATUS_ACTIVE, $services[0]->fresh()->status);

        foreach (array_slice($services, 1) as $surnumeraire) {
            $this->assertSame(Service::STATUS_INACTIVE, $surnumeraire->fresh()->status);
        }
    }

    /**
     * Retirés, pas supprimés : reprendre un palier plus large doit les rendre
     * tels quels. C'est ce qui distingue une restriction d'une sanction.
     */
    public function test_the_hidden_services_are_not_deleted(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());
        $services = $this->publishServices($provider, 3);

        app(QuotaService::class)->enforceServiceCap($provider);

        foreach ($services as $service) {
            $this->assertNotNull(Service::withTrashed()->find($service->id));
        }
    }

    public function test_an_unlimited_plan_never_hides_anything(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_ACTIVE, now()->addDays(20));
        $services = $this->publishServices($provider, 5);

        $this->assertSame(0, app(QuotaService::class)->enforceServiceCap($provider));

        foreach ($services as $service) {
            $this->assertSame(Service::STATUS_ACTIVE, $service->fresh()->status);
        }
    }

    /**
     * Un abonnement gratuit ne s'éteint pas : le laisser expirer sortirait le
     * prestataire des recherches tous les trente jours sans qu'il ait rien à
     * régler, et l'y ferait rentrer par un formulaire de paiement à 0 FCFA.
     */
    public function test_a_lapsed_free_subscription_renews_itself(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_ACTIVE, now()->subDay(), $this->gratuit);

        $expires = app(SubscriptionService::class)->expireLapsed();

        $subscription = $provider->subscriptions()->first()->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->isFuture());

        // La reconduction n'est pas une expiration : la compter fausserait le
        // nombre de prestataires qui viennent de sortir des recherches.
        $this->assertSame(0, $expires);
    }

    public function test_a_lapsed_paid_subscription_still_expires(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_ACTIVE, now()->subDay());

        $this->assertSame(1, app(SubscriptionService::class)->expireLapsed());
        $this->assertSame(
            Subscription::STATUS_EXPIRED,
            $provider->subscriptions()->first()->fresh()->status,
        );
    }

    /**
     * Relancer un abonnement gratuit reviendrait à réclamer un règlement qui
     * n'existe pas.
     */
    public function test_a_free_subscription_never_gets_an_expiry_reminder(): void
    {
        $provider = $this->providerWithSubscription(
            Subscription::STATUS_ACTIVE, now()->addDay(), $this->gratuit,
        );
        $provider->update(['phone' => '677001122']);

        $this->assertSame(0, app(SubscriptionService::class)->sendExpiryReminders());
        $this->assertNull($provider->subscriptions()->first()->fresh()->last_reminder_day);
    }

    public function test_the_free_plan_offers_no_paid_feature(): void
    {
        $this->assertFalse($this->gratuit->is_featured);
        $this->assertFalse($this->gratuit->has_ai_writing);
        $this->assertFalse($this->gratuit->has_stats);
        $this->assertFalse($this->gratuit->allowsUnlimitedServices());
        $this->assertFalse($this->gratuit->allowsUnlimitedRequests());
    }

    /**
     * Le palier de l'essai reste le plus complet : la formule gratuite ne doit
     * pas se substituer aux trente jours offerts à l'inscription.
     */
    public function test_the_free_plan_never_becomes_the_trial_plan(): void
    {
        $this->assertSame($this->pro->id, Plan::trialPlan()?->id);
    }

    /**
     * La page d'engagement énonce les restrictions avant qu'on y souscrive :
     * un prestataire qui les découvre après coup se croit victime d'une panne.
     */
    public function test_the_free_checkout_page_states_the_restrictions(): void
    {
        $provider = $this->providerWithSubscription(Subscription::STATUS_EXPIRED, now()->subDay());

        $this->actingAs($provider)
            ->get(route('provider.subscription.checkout', $this->gratuit))
            ->assertOk()
            ->assertSee(__('ui.subscription.free_excluded'))
            ->assertSee(__('ui.plans.stats'))
            ->assertSee(trans_choice('ui.plans.services_limit', 1, ['count' => 1]))
            // Aucun champ de paiement : la formule ne passe pas par l'opérateur.
            ->assertDontSee(__('ui.payment.phone'));
    }

    public function test_a_guest_cannot_activate_the_free_plan(): void
    {
        $this->post(route('provider.subscription.free', $this->gratuit))
            ->assertRedirect(route('login'));
    }

    public function test_a_client_cannot_activate_the_free_plan(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->post(route('provider.subscription.free', $this->gratuit))
            ->assertForbidden();
    }

    private function providerWithSubscription(string $status, $endsAt, ?Plan $plan = null): User
    {
        $provider = User::factory()->provider()->create();

        Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => ($plan ?? $this->pro)->id,
            'status' => $status,
            'starts_at' => now()->subMonth(),
            'ends_at' => $endsAt,
        ]);

        return $provider->refresh();
    }

    /**
     * @return array<int, Service>
     */
    private function publishServices(User $provider, int $count): array
    {
        $category = ServiceCategory::factory()->create();

        return collect(range(1, $count))
            ->map(fn (int $i) => Service::factory()->create([
                'provider_id' => $provider->id,
                'category_id' => $category->id,
                'status' => Service::STATUS_ACTIVE,
            ]))
            ->all();
    }
}
