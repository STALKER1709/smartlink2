<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscribeFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    private Plan $essential;

    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->essential = Plan::factory()->create();
        $this->pro = Plan::factory()->pro()->create();
        $this->provider = User::factory()->provider()->create(['phone' => '677001122']);
        $this->subscribeProvider($this->provider, Plan::CODE_PRO);

        // Le rappel de l'opérateur exige un secret : sans lui, il se ferme.
        config()->set('campay.webhook_secret', 'le-vrai-secret');
    }

    public function test_a_provider_reaches_the_checkout_page_for_a_plan(): void
    {
        $this->actingAs($this->provider)
            ->get(route('provider.subscription.checkout', $this->essential))
            ->assertOk()
            ->assertViewIs('provider.subscription.checkout')
            ->assertSee($this->essential->name())
            ->assertSee(__('ui.subscription.no_auto_debit'));
    }

    public function test_a_confirmed_payment_activates_the_chosen_plan_and_extends_the_due_date(): void
    {
        // Sans identifiants, Campay répond SUCCESSFUL immédiatement.
        $before = $this->provider->activeSubscription()->ends_at;

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ])
            ->assertRedirect(route('provider.subscription.show'))
            ->assertSessionHas('status');

        $subscription = $this->provider->activeSubscription();

        $this->assertSame($this->essential->id, $subscription->plan_id);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->greaterThan($before));

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame($this->essential->id, $payment->plan_id);
        $this->assertSame($this->essential->price_xaf, $payment->amount_xaf);
    }

    public function test_a_pending_collection_leaves_the_plan_untouched_until_the_callback(): void
    {
        $this->withRealCampay(['reference' => 'CAMPAY-PENDING', 'ussd_code' => '*126#']);

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ])
            ->assertRedirect(route('provider.subscription.show'));

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame('CAMPAY-PENDING', $payment->campay_reference);

        // Le palier ne change qu'au règlement effectif.
        $this->assertSame($this->pro->id, $this->provider->activeSubscription()->plan_id);

        $this->withHeader('X-Campay-Signature', 'le-vrai-secret')
            ->postJson(route('payments.webhook'), [
                'external_reference' => $payment->internal_reference,
                'status' => 'SUCCESSFUL',
                'reference' => 'CAMPAY-PENDING',
            ])->assertOk();

        $this->assertSame($this->essential->id, $this->provider->activeSubscription()->plan_id);
    }

    public function test_a_refused_collection_reports_the_failure_without_charging_the_plan(): void
    {
        $this->withRealCampay([]);

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('phone');

        $this->assertSame(Payment::STATUS_FAILED, Payment::firstOrFail()->status);
        $this->assertSame($this->pro->id, $this->provider->activeSubscription()->plan_id);
    }

    public function test_a_second_attempt_reuses_the_pending_collection_instead_of_charging_twice(): void
    {
        $this->withRealCampay(['reference' => 'CAMPAY-PENDING']);

        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->provider)
                ->post(route('provider.subscription.subscribe', $this->essential), [
                    'phone' => '677001122',
                    'operator' => 'mtn',
                ]);
        }

        $this->assertSame(1, Payment::count());
    }

    public function test_changing_plan_mid_flight_abandons_the_pending_collection(): void
    {
        $this->withRealCampay(['reference' => 'CAMPAY-PENDING']);

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ]);

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->pro), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ]);

        $this->assertSame(2, Payment::count());
        $this->assertSame(
            Payment::STATUS_CANCELLED,
            Payment::where('plan_id', $this->essential->id)->firstOrFail()->status,
        );

        $live = Payment::where('plan_id', $this->pro->id)->firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $live->status);
        $this->assertSame($this->pro->price_xaf, $live->amount_xaf);
    }

    public function test_an_expired_provider_can_subscribe_again_and_reappears_in_search(): void
    {
        $this->provider->subscriptions()->update([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subDays(5),
        ]);
        $this->artisan('subscriptions:refresh');

        $this->assertFalse($this->provider->providerProfile->fresh()->is_listed);

        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->pro), [
                'phone' => '677001122',
                'operator' => 'orange',
            ])->assertRedirect(route('provider.subscription.show'));

        $this->assertTrue($this->provider->hasUsableSubscription());
        $this->assertTrue($this->provider->providerProfile->fresh()->is_listed);
    }

    public function test_the_phone_number_must_be_cameroonian(): void
    {
        $this->actingAs($this->provider)
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '0612345678',
                'operator' => 'mtn',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame(0, Payment::count());
    }

    public function test_a_client_cannot_subscribe(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->post(route('provider.subscription.subscribe', $this->essential), [
                'phone' => '677001122',
                'operator' => 'mtn',
            ])
            ->assertForbidden();
    }

    public function test_an_inactive_plan_is_not_purchasable(): void
    {
        $this->essential->update(['is_active' => false]);

        $this->actingAs($this->provider)
            ->get(route('provider.subscription.checkout', $this->essential))
            ->assertNotFound();
    }

    /**
     * Bascule Campay en mode « identifiants renseignés » pour exercer le
     * vrai chemin HTTP plutôt que la réponse simulée du bac à sable.
     *
     * @param  array<string, mixed>  $collectResponse
     */
    private function withRealCampay(array $collectResponse): void
    {
        config()->set('campay.username', 'compte-test');
        config()->set('campay.password', 'secret-test');

        Http::fake([
            '*/token/' => Http::response(['token' => 'jeton-test']),
            '*/collect/' => Http::response($collectResponse),
        ]);
    }
}
