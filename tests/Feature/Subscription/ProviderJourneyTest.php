<?php

namespace Tests\Feature\Subscription;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Le parcours complet d'un prestataire, bout en bout : inscription, essai,
 * publication, réception d'une demande, expiration, règlement, retour en
 * ligne. Chaque étape est couverte ailleurs isolément ; ce test vérifie
 * qu'elles s'enchaînent réellement.
 */
class ProviderJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provider_goes_from_registration_to_a_paid_subscription(): void
    {
        $essential = Plan::factory()->create();
        $pro = Plan::factory()->pro()->create();
        $category = ServiceCategory::factory()->create();
        config()->set('campay.webhook_secret', 'le-vrai-secret');

        // 1. Inscription : l'essai s'ouvre et le prestataire est visible.
        $this->post(route('register'), [
            'name' => 'Jean-Paul Etoo',
            'email' => 'jp@example.cm',
            'phone' => '677112233',
            'role' => User::ROLE_PROVIDER,
            'business_name' => 'Jean-Paul Plomberie',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ])->assertRedirect(route('dashboard', absolute: false));

        $provider = User::where('email', 'jp@example.cm')->firstOrFail();
        $this->assertSame(Subscription::STATUS_TRIALING, $provider->activeSubscription()->status);
        $this->assertTrue($provider->providerProfile->fresh()->is_listed);

        // 2. Publication d'un service, visible publiquement.
        $this->actingAs($provider)->post(route('provider.services.store'), [
            'title' => 'Depannage plomberie 24h',
            'category_id' => $category->id,
            'description' => 'Je repare les fuites et je pose des chauffe-eau a Douala.',
            'city' => 'Douala',
        ])->assertRedirect();

        $service = Service::where('provider_id', $provider->id)->firstOrFail();
        $this->asVisitor();
        $this->get(route('services.index'))->assertOk()->assertSee($service->title);

        // 3. Un client envoie une demande, que le prestataire lit.
        $client = User::factory()->client()->create();
        $this->actingAs($client)->post(route('requests.store'), [
            'service_id' => $service->id,
            'message' => 'Bonjour, j\'ai une fuite sous l\'evier.',
            'action' => 'send',
        ])->assertRedirect();

        $serviceRequest = ServiceRequest::firstOrFail();
        $this->actingAs($provider)
            ->get(route('requests.show', $serviceRequest))
            ->assertOk()
            ->assertViewIs('requests.show');
        $this->assertSame(ServiceRequest::STATUS_VIEWED, $serviceRequest->fresh()->status);

        // 4. L'essai expire : le service sort des recherches, le compte reste.
        $provider->subscriptions()->update(['ends_at' => now()->subDay()]);
        $this->artisan('subscriptions:refresh')->assertSuccessful();

        $this->asVisitor();
        $this->get(route('services.index'))->assertOk()->assertDontSee($service->title);
        $this->get(route('services.show', $service))->assertNotFound();

        $this->actingAs($provider)
            ->get(route('requests.show', $serviceRequest))
            ->assertOk()
            ->assertViewIs('requests.show');
        $this->actingAs($provider)->get(route('conversations.index'))->assertOk();
        $this->actingAs($provider)->get(route('provider.services.create'))->assertForbidden();

        // 5. Règlement Mobile Money : la collecte part en attente.
        $this->actingAs($provider)
            ->post(route('provider.subscription.subscribe', $essential), [
                'phone' => '677112233',
                'operator' => 'mtn',
            ])->assertRedirect(route('provider.subscription.show'));

        $payment = Payment::firstOrFail();
        $this->assertSame($essential->id, $payment->plan_id);

        // 6. L'opérateur confirme : le palier prend effet, le service revient.
        $this->withHeader('X-Campay-Signature', 'le-vrai-secret')
            ->postJson(route('payments.webhook'), [
                'external_reference' => $payment->internal_reference,
                'status' => 'SUCCESSFUL',
                'reference' => 'CAMPAY-OK',
            ])->assertOk();

        $subscription = $provider->activeSubscription();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame($essential->id, $subscription->plan_id);

        $this->asVisitor();
        $this->get(route('services.index'))->assertOk()->assertSee($service->title);
        $this->get(route('services.show', $service))->assertOk();
        $this->actingAs($provider)->get(route('provider.services.create'))->assertOk();

        // 7. Le palier Essentiel borne ce que l'essai laissait illimité.
        $this->assertSame($essential->max_services, $provider->currentPlan()->max_services);
        $this->assertNotSame($pro->id, $subscription->plan_id);
    }

    /**
     * actingAs() vaut pour tout le reste du test : sans déconnexion explicite,
     * les vérifications « ce que voit un visiteur » se feraient en réalité avec
     * le prestataire connecté, qui garde accès à ses propres pages masquées.
     */
    private function asVisitor(): void
    {
        Auth::logout();
        $this->app['auth']->forgetGuards();
    }
}
