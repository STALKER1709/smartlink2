<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrôle du flux d'abonnement sur ses cas limites — c'est le seul flux
 * d'argent du produit, et chacun de ces scénarios se traduit soit par un
 * prestataire qui paie sans rien recevoir, soit par un cycle offert.
 */
class SubscriptionAuditTest extends TestCase
{
    use RefreshDatabase;

    private function prestataireAvecAbonnement(string $statut, ?string $fin = null): User
    {
        $provider = User::factory()->provider()->create();
        $plan = Plan::factory()->create();

        Subscription::create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'status' => $statut,
            'starts_at' => now()->subDays(30),
            'ends_at' => $fin ? now()->parse($fin) : now()->addDays(5),
        ]);

        return $provider;
    }

    public function test_a_payment_credited_twice_does_not_offer_a_second_cycle(): void
    {
        $provider = $this->prestataireAvecAbonnement(Subscription::STATUS_ACTIVE);
        $abonnement = $provider->subscriptions()->firstOrFail();
        $echeanceInitiale = $abonnement->ends_at->copy();

        $payment = Payment::create([
            'subscription_id' => $abonnement->id,
            'plan_id' => $abonnement->plan_id,
            'payer_id' => $provider->id,
            'amount_xaf' => 2500,
            'operator' => 'mtn',
            'phone' => '677000000',
            'status' => Payment::STATUS_SUCCESS,
            'internal_reference' => 'SL-DOUBLE',
        ]);

        $service = app(SubscriptionService::class);
        $service->recordSuccessfulPayment($payment);
        $apresPremier = $abonnement->refresh()->ends_at->copy();

        $service->recordSuccessfulPayment($payment->refresh());
        $apresSecond = $abonnement->refresh()->ends_at;

        $this->assertTrue($apresPremier->greaterThan($echeanceInitiale), 'Le premier règlement doit prolonger.');
        $this->assertTrue(
            $apresSecond->equalTo($apresPremier),
            'Rejouer le même règlement offre un second cycle : '
            .$apresPremier->toDateString().' → '.$apresSecond->toDateString()
        );
    }

    public function test_a_late_callback_on_an_abandoned_collection_is_ignored(): void
    {
        $provider = $this->prestataireAvecAbonnement(Subscription::STATUS_ACTIVE);
        $abonnement = $provider->subscriptions()->firstOrFail();

        // Collecte abandonnée par un changement de palier, puis validée malgré
        // tout par le prestataire sur son téléphone.
        $abandonnee = Payment::create([
            'subscription_id' => $abonnement->id,
            'plan_id' => $abonnement->plan_id,
            'payer_id' => $provider->id,
            'amount_xaf' => 2500,
            'operator' => 'mtn',
            'phone' => '677000000',
            'status' => Payment::STATUS_CANCELLED,
            'internal_reference' => 'SL-ABANDON',
        ]);

        $echeance = $abonnement->ends_at->copy();

        $reponse = $this->postJson(route('payments.webhook'), [
            'data' => ['reference' => 'ref-op', 'metadata' => ['reference_interne' => $abandonnee->internal_reference]],
        ]);

        $this->assertSame($echeance->toDateTimeString(), $abonnement->refresh()->ends_at->toDateTimeString(),
            'Une collecte abandonnée ne doit pas créditer.');
        $this->assertSame(Payment::STATUS_CANCELLED, $abandonnee->refresh()->status);
    }

    public function test_an_expired_provider_who_pays_recovers_visibility(): void
    {
        $provider = $this->prestataireAvecAbonnement(Subscription::STATUS_EXPIRED, '-2 days');
        $abonnement = $provider->subscriptions()->firstOrFail();

        $this->assertFalse($provider->refresh()->hasUsableSubscription(), 'Départ : abonnement échu.');

        $payment = Payment::create([
            'subscription_id' => $abonnement->id,
            'plan_id' => $abonnement->plan_id,
            'payer_id' => $provider->id,
            'amount_xaf' => 2500,
            'operator' => 'mtn',
            'phone' => '677000000',
            'status' => Payment::STATUS_SUCCESS,
            'internal_reference' => 'SL-RETOUR',
        ]);

        app(SubscriptionService::class)->recordSuccessfulPayment($payment);

        $abonnement->refresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $abonnement->status);
        $this->assertTrue($abonnement->ends_at->isFuture());
        // Le cycle repart de maintenant, pas de l'échéance passée.
        $this->assertTrue($abonnement->ends_at->greaterThan(now()->addDays(28)));
        $this->assertTrue($provider->refresh()->hasUsableSubscription());
    }

    public function test_a_provider_never_gets_a_second_trial(): void
    {
        $provider = User::factory()->provider()->create();
        Plan::factory()->pro()->create();   // trialPlan() ne reconnaît que celui-ci

        $service = app(SubscriptionService::class);
        $premier = $service->startTrial($provider);
        $second = $service->startTrial($provider->refresh());

        $this->assertNotNull($premier);
        $this->assertNull($second, 'Un second essai gratuit a été ouvert.');
        $this->assertSame(1, $provider->subscriptions()->count());
    }

    public function test_paying_switches_the_plan_only_once_the_money_has_landed(): void
    {
        $provider = $this->prestataireAvecAbonnement(Subscription::STATUS_ACTIVE);
        $abonnement = $provider->subscriptions()->firstOrFail();
        $ancienPalier = $abonnement->plan_id;
        $nouveauPalier = Plan::factory()->pro()->create(['price_xaf' => 7500]);

        $payment = Payment::create([
            'subscription_id' => $abonnement->id,
            'plan_id' => $nouveauPalier->id,
            'payer_id' => $provider->id,
            'amount_xaf' => 7500,
            'operator' => 'mtn',
            'phone' => '677000000',
            'status' => Payment::STATUS_PENDING,
            'internal_reference' => 'SL-PALIER',
        ]);

        $this->assertSame($ancienPalier, $abonnement->refresh()->plan_id,
            'Le palier ne doit pas changer tant que la collecte est en attente.');

        $payment->update(['status' => Payment::STATUS_SUCCESS]);
        app(SubscriptionService::class)->recordSuccessfulPayment($payment->refresh());

        $this->assertSame($nouveauPalier->id, $abonnement->refresh()->plan_id);
    }
}
