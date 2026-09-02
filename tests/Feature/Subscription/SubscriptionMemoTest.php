<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * L'abonnement en cours est mémoïsé sur l'instance, le temps d'une requête
 * HTTP : sans cela, une seule page de prestataire rejouait la même requête
 * jusqu'à sept fois.
 *
 * Une mémoïsation n'est sûre que si elle tombe quand la donnée change. Ces
 * tests couvrent les deux moitiés : elle épargne bien les requêtes, et elle ne
 * rend jamais une valeur périmée.
 */
class SubscriptionMemoTest extends TestCase
{
    use RefreshDatabase;

    private function prestataireAbonne(): User
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $this->subscribeProvider($provider->refresh());

        return $provider->refresh();
    }

    /**
     * @return array<int, string>
     */
    private function requetesPendant(callable $action): array
    {
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $action();

        $requetes = array_column(DB::connection()->getQueryLog(), 'query');
        DB::connection()->disableQueryLog();

        return $requetes;
    }

    public function test_repeated_reads_hit_the_database_once(): void
    {
        $provider = $this->prestataireAbonne();

        $requetes = $this->requetesPendant(function () use ($provider) {
            $provider->activeSubscription();
            $provider->activeSubscription();
            $provider->currentPlan();
            $provider->hasUsableSubscription();
        });

        $surAbonnements = array_filter(
            $requetes,
            fn (string $sql) => str_contains($sql, 'from "subscriptions"')
        );

        $this->assertCount(1, $surAbonnements, 'Quatre lectures ne doivent produire qu\'une requête.');
    }

    /**
     * La moitié qui rend la mémoïsation sûre : une page qui ne demande rien
     * ne doit pas payer la requête.
     */
    public function test_an_absent_subscription_is_remembered_too(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $provider->refresh();

        $requetes = $this->requetesPendant(function () use ($provider) {
            $provider->activeSubscription();
            $provider->activeSubscription();
        });

        $surAbonnements = array_filter(
            $requetes,
            fn (string $sql) => str_contains($sql, 'from "subscriptions"')
        );

        $this->assertCount(1, $surAbonnements);
        $this->assertNull($provider->activeSubscription());
    }

    /**
     * `refresh()` recharge l'état depuis la base : le mémo doit tomber avec le
     * reste, sinon un modèle rafraîchi rendrait une valeur périmée.
     */
    public function test_refresh_drops_the_memo(): void
    {
        $provider = $this->prestataireAbonne();

        $this->assertNotNull($provider->activeSubscription());

        Subscription::where('user_id', $provider->id)
            ->update(['status' => Subscription::STATUS_EXPIRED, 'ends_at' => now()->subDay()]);

        $this->assertNotNull(
            $provider->activeSubscription(),
            'Sans rafraîchissement, le mémo tient — c\'est le comportement attendu le temps d\'une requête.'
        );

        $provider->refresh();

        $this->assertNull(
            $provider->activeSubscription(),
            'Après rafraîchissement, la base fait foi.'
        );
    }

    public function test_forgetting_the_memo_forces_a_fresh_read(): void
    {
        $provider = $this->prestataireAbonne();
        $ancienPalier = $provider->currentPlan()?->id;

        $autrePalier = Plan::factory()->create(['code' => 'palier-test']);
        Subscription::where('user_id', $provider->id)->update(['plan_id' => $autrePalier->id]);

        $this->assertSame($ancienPalier, $provider->currentPlan()?->id);

        $provider->forgetActiveSubscription();

        $this->assertSame($autrePalier->id, $provider->currentPlan()?->id);
    }

    /**
     * Bout en bout : après un règlement abouti, l'instance de la requête ne
     * doit plus annoncer l'ancien palier.
     */
    public function test_a_successful_payment_invalidates_the_memo(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $provider->refresh();

        $abonnements = app(SubscriptionService::class);
        $abonnements->startTrial($provider);
        $provider->refresh();

        $palierPaye = Plan::factory()->create(['code' => 'palier-paye', 'price_xaf' => 2500]);

        // Le mémo est chargé avant le paiement, comme le ferait une page
        // rendue juste avant la validation.
        $provider->activeSubscription();

        $abonnements->requestPayment($provider, $palierPaye, '677000000', 'mtn');

        $this->assertSame(
            $palierPaye->id,
            $provider->currentPlan()?->id,
            'Après règlement, l\'instance de la requête doit voir le nouveau palier.'
        );
    }
}
