<?php

namespace Tests\Feature\Subscription;

use App\Contracts\PaymentProvider;
use App\Contracts\WebhookEvent;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\CollectionResult;
use App\Services\SmsService;
use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Tests\TestCase;

/**
 * Comportement du contrôleur de rappel, indépendamment du fournisseur :
 * l'authentification et la lecture lui sont déléguées, le contrôleur décide
 * seulement de ce qu'il en fait.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le rappel vient de l'extérieur et ne peut pas porter de jeton CSRF. La
     * protection étant désactivée d'office pendant les tests, l'exemption se
     * vérifie sur la configuration du middleware lui-même.
     */
    public function test_the_webhook_path_is_exempt_from_csrf_verification(): void
    {
        $middleware = $this->app->make(PreventRequestForgery::class);

        $this->assertContains('payments/webhook', $middleware->getExcludedPaths());
    }

    public function test_a_confirmed_payment_extends_the_subscription(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;
        $this->fakeProvider($payment->internal_reference, 'success');

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame('HRSK-REF', $payment->provider_reference);
        $this->assertNotNull($payment->paid_at);

        $subscription = $payment->subscription->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->greaterThan($endsAt));
    }

    public function test_a_refused_payment_is_recorded_without_extending_anything(): void
    {
        $payment = $this->pendingPayment();
        $endsAt = $payment->subscription->ends_at;
        $this->fakeProvider($payment->internal_reference, 'failed');

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertEquals(
            $endsAt->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    /**
     * Le rappel arrive après coup : le prestataire a quitté la page depuis
     * longtemps et rien à l'écran ne lui apprendra le refus. Sans ce SMS, il
     * se croit à jour et découvre la coupure en constatant qu'il ne reçoit
     * plus de demandes — sans jamais faire le lien avec son règlement.
     */
    public function test_a_refused_payment_is_told_to_the_provider(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'failed');

        $sms = $this->mock(SmsService::class);
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $phone, string $message) => $phone === $payment->payer->phone
                && str_contains($message, 'Échec du paiement'));

        $this->postJson(route('payments.webhook'), [])->assertOk();
    }

    /**
     * La vraie course : le paiement est encore en attente quand le contrôleur
     * le lit, et un autre rappel le tranche avant que la transaction ne
     * verrouille la ligne.
     *
     * Ce rappel-ci ne doit alors rien envoyer — sans quoi le prestataire
     * reçoit deux SMS pour un seul règlement — et sa trace doit dire qu'il n'a
     * rien tranché, au lieu d'écrire un deuxième « Règlement refusé ».
     */
    public function test_a_callback_that_settles_nothing_stays_silent(): void
    {
        $capture = new TestHandler;
        Log::getLogger()->pushHandler($capture);

        $payment = $this->pendingPayment();

        $this->fakeProvider($payment->internal_reference, 'failed', pendant: function () use ($payment) {
            Payment::whereKey($payment->id)->update(['status' => Payment::STATUS_FAILED]);
        });

        $sms = $this->mock(SmsService::class);
        $sms->shouldNotReceive('send');

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertTrue(
            $capture->hasInfoThatContains('rappel concurrent'),
            'La trace doit distinguer un rappel qui ne tranche rien d\'un vrai refus.',
        );
    }

    /**
     * Un rappel sur un paiement déjà tranché s'arrête avant même la lecture du
     * statut. Chemin voisin du précédent, mais distinct : il n'atteint jamais
     * la transaction.
     */
    public function test_a_callback_on_an_already_settled_payment_sends_no_message(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => Payment::STATUS_FAILED]);
        $this->fakeProvider($payment->internal_reference, 'failed');

        $sms = $this->mock(SmsService::class);
        $sms->shouldNotReceive('send');

        $this->postJson(route('payments.webhook'), [])->assertOk();
    }

    /**
     * PENDING et HOLD — la revue anti-blanchiment — ne concluent rien. Créditer
     * sur un HOLD reviendrait à ouvrir un abonnement encore refusable.
     */
    public function test_a_status_that_settles_nothing_leaves_the_payment_pending(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, null);

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'pending']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    /**
     * Le statut annoncé dans le rappel n'est jamais cru : le contrôleur relit
     * l'état chez le fournisseur, qui seul fait foi.
     */
    public function test_the_announced_status_is_never_trusted_over_the_api(): void
    {
        $payment = $this->pendingPayment();

        // Le rappel prétend « SUCCESS », l'API dit que rien n'est réglé.
        $this->fakeProvider($payment->internal_reference, 'failed');

        $this->postJson(route('payments.webhook'), ['status' => 'SUCCESS'])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
    }

    public function test_an_unauthenticated_callback_is_refused(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'success', authentic: false);

        $this->postJson(route('payments.webhook'), [])
            ->assertForbidden()
            ->assertJson(['status' => 'rejected']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    /**
     * HR-Skills envoie depuis sa console des événements de test qui ne portent
     * aucun paiement. Ils sont authentiques : les refuser ferait croire au
     * fournisseur que notre point d'entrée est en panne, et l'a effectivement
     * fait en production. On en accuse réception.
     */
    public function test_an_authentic_callback_without_a_payment_is_acknowledged(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider(null, 'success');

        $this->postJson(route('payments.webhook'), ['event' => 'webhook.test'])
            ->assertOk()
            ->assertJson(['status' => 'acknowledged']);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_replaying_a_callback_does_not_extend_the_subscription_twice(): void
    {
        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'success');

        $this->postJson(route('payments.webhook'), [])->assertOk();
        $afterFirst = $payment->subscription->fresh()->ends_at;

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertEquals(
            $afterFirst->timestamp,
            $payment->subscription->fresh()->ends_at->timestamp,
        );
    }

    /**
     * Les journaux d'accès de l'hébergeur ne montrent que le code HTTP, et il
     * vaut 200 aussi bien pour un abonnement crédité que pour un rappel sans
     * effet. Sans cette trace, savoir ce qui s'est passé en production
     * demanderait d'aller lire la base.
     */
    public function test_every_outcome_leaves_a_trace(): void
    {
        // Un vrai gestionnaire Monolog plutôt qu'un double : le contrôleur
        // n'est pas seul à journaliser pendant la requête, et un double du
        // gestionnaire complet casse les canaux nommés que d'autres services
        // utilisent.
        $capture = new TestHandler;
        Log::getLogger()->pushHandler($capture);

        $payment = $this->pendingPayment();
        $this->fakeProvider($payment->internal_reference, 'success');

        $this->postJson(route('payments.webhook'), [])->assertOk();

        $this->assertTrue(
            $capture->hasInfoThatContains('abonnement crédité'),
            "L'issue du rappel n'a laissé aucune trace.",
        );
        $this->assertTrue($capture->hasInfoThatContains($payment->internal_reference));
    }

    public function test_a_callback_on_an_unknown_payment_is_acknowledged_without_effect(): void
    {
        $this->fakeProvider('SL-INCONNUE', 'success');

        $this->postJson(route('payments.webhook'), [])
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    /**
     * Le fournisseur est remplacé par un double : le contrôleur ne doit rien
     * savoir de la signature ni de la forme des charges utiles.
     */
    /**
     * @param  ?Closure  $pendant  Joué à l'intérieur de `status()`, c'est-à-dire
     *                             dans la fenêtre exacte où un rappel concurrent
     *                             peut trancher : après la lecture du
     *                             contrôleur, avant que sa transaction ne
     *                             verrouille la ligne.
     */
    private function fakeProvider(?string $internalReference, ?string $status, bool $authentic = true, ?Closure $pendant = null): void
    {
        $fake = new class($internalReference, $status, $authentic, $pendant) implements PaymentProvider
        {
            public function __construct(
                private readonly ?string $internalReference,
                private readonly ?string $status,
                private readonly bool $authentic,
                private readonly ?Closure $pendant = null,
            ) {}

            public function collect(
                string $phone,
                string $operator,
                int $amountXaf,
                string $description,
                string $reference,
            ): CollectionResult {
                return CollectionResult::pending('HRSK-REF');
            }

            public function status(string $providerReference): ?string
            {
                ($this->pendant ?? fn () => null)();

                return $this->status;
            }

            public function isAuthentic(Request $request): bool
            {
                return $this->authentic;
            }

            public function readWebhook(Request $request): ?WebhookEvent
            {
                return $this->internalReference === null
                    ? null
                    : new WebhookEvent('HRSK-REF', $this->internalReference);
            }
        };

        $this->app->instance(PaymentProvider::class, $fake);
    }

    private function pendingPayment(): Payment
    {
        $provider = User::factory()->provider()->create();
        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'user_id' => $provider->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'ends_at' => now()->addDays(3),
        ]);

        return Payment::factory()->create([
            'subscription_id' => $subscription->id,
            'payer_id' => $provider->id,
            'amount_xaf' => $plan->price_xaf,
        ]);
    }
}
