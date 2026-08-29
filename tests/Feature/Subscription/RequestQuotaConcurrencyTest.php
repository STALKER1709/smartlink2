<?php

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le compteur de demandes lues porte toute la valeur commerciale de la grille
 * tarifaire : c'est lui qui sépare le palier Essentiel du palier Pro, et c'est
 * lui qui décide si un prestataire reste visible en recherche.
 *
 * Deux requêtes HTTP simultanées, c'est deux processus PHP qui chargent chacun
 * leur propre instance du profil. Un lire-modifier-écrire y perd une
 * incrémentation sans rien signaler. Ces tests reproduisent exactement cette
 * situation en chargeant deux instances distinctes depuis la base.
 */
class RequestQuotaConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function prestataireAuPalier(?int $maxDemandes): User
    {
        $plan = Plan::factory()->create([
            'max_monthly_requests' => $maxDemandes,
            'max_services' => 10,
        ]);

        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $this->subscribeProvider($provider->refresh(), $plan->code);

        return $provider->refresh();
    }

    /**
     * Deux lectures concurrentes doivent décompter deux fois.
     *
     * Avant correctif, les deux instances lisaient la même valeur et
     * écrivaient la même incrémentation : le compteur montait de 1 au lieu
     * de 2, et le prestataire lisait une demande de plus que son palier.
     */
    public function test_two_concurrent_reads_both_count(): void
    {
        $provider = $this->prestataireAuPalier(10);
        $quotas = app(QuotaService::class);

        // Chaque instance représente un processus PHP distinct. Les deux
        // profils sont chargés *avant* la première écriture — c'est ce que
        // fait la concurrence réelle, et c'est là que l'incrémentation se
        // perdait. Charger paresseusement après coup masquerait le défaut.
        $processusA = User::find($provider->id);
        $processusB = User::find($provider->id);
        $processusA->providerProfile;
        $processusB->providerProfile;

        $quotas->consumeRequestRead($processusA);
        $quotas->consumeRequestRead($processusB);

        $this->assertSame(2, $quotas->requestsRead($provider->refresh()));
    }

    /**
     * Le dernier jeton ne se distribue qu'une fois.
     *
     * C'est le cas qui compte vraiment : au plafond moins un, deux lectures
     * simultanées doivent en voir une passer et l'autre être refusée.
     */
    public function test_the_last_slot_is_granted_only_once(): void
    {
        $provider = $this->prestataireAuPalier(1);
        $quotas = app(QuotaService::class);

        $processusA = User::find($provider->id);
        $processusB = User::find($provider->id);
        $processusA->providerProfile;
        $processusB->providerProfile;

        $premier = $quotas->consumeRequestRead($processusA);
        $second = $quotas->consumeRequestRead($processusB);

        $this->assertTrue($premier, 'La première lecture devait passer.');
        $this->assertFalse($second, 'La seconde devait être refusée : le palier n\'autorise qu\'une demande.');
        $this->assertSame(1, $quotas->requestsRead($provider->refresh()));
    }

    public function test_an_unlimited_plan_never_refuses(): void
    {
        $provider = $this->prestataireAuPalier(null);
        $quotas = app(QuotaService::class);

        foreach (range(1, 5) as $ignored) {
            $this->assertTrue($quotas->consumeRequestRead(User::find($provider->id)));
        }
    }

    /**
     * Le compteur se remet à zéro au changement de mois, sans tâche planifiée.
     * L'incrémentation atomique ne doit pas avoir cassé cette bascule.
     */
    public function test_the_counter_resets_on_a_new_month(): void
    {
        $provider = $this->prestataireAuPalier(2);
        $quotas = app(QuotaService::class);

        $quotas->consumeRequestRead($provider);
        $quotas->consumeRequestRead(User::find($provider->id));

        $this->assertSame(2, $quotas->requestsRead($provider->refresh()));
        $this->assertFalse($quotas->consumeRequestRead(User::find($provider->id)));

        $this->travel(1)->months();

        $this->assertSame(0, $quotas->requestsRead($provider->refresh()));
        $this->assertTrue($quotas->consumeRequestRead(User::find($provider->id)));
        $this->assertSame(1, $quotas->requestsRead($provider->refresh()));
    }

    public function test_a_provider_without_a_subscription_is_refused(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $this->assertFalse(app(QuotaService::class)->consumeRequestRead($provider->refresh()));
    }

    /**
     * Bout en bout : au plafond, l'écran de demande passe en « verrouillé »
     * et la demande n'est pas marquée comme vue.
     */
    public function test_the_request_page_locks_once_the_cap_is_reached(): void
    {
        $provider = $this->prestataireAuPalier(1);
        $client = User::factory()->client()->create();

        $premiere = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'status' => ServiceRequest::STATUS_SENT,
        ]);

        $seconde = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'status' => ServiceRequest::STATUS_SENT,
        ]);

        $this->actingAs($provider)->get(route('requests.show', $premiere))
            ->assertOk()
            ->assertViewIs('requests.show');

        $this->actingAs($provider)->get(route('requests.show', $seconde))
            ->assertOk()
            ->assertViewIs('requests.locked');

        $this->assertSame(
            ServiceRequest::STATUS_SENT,
            $seconde->refresh()->status,
            'Une demande refusée par le plafond ne doit pas passer en « vue ».'
        );
    }
}
