<?php

namespace Tests\Feature\Provider;

use App\Models\Plan;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ProviderStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = User::factory()->provider()->create();
        $this->subscribeProvider($this->provider, Plan::CODE_PRO);
    }

    public function test_a_pro_provider_reaches_its_statistics(): void
    {
        $this->actingAs($this->provider)
            ->get(route('provider.statistics.index'))
            ->assertOk()
            ->assertViewIs('provider.statistics.index');
    }

    public function test_a_plan_without_statistics_is_refused(): void
    {
        $this->provider->currentPlan()->update(['has_stats' => false]);

        $this->actingAs($this->provider)
            ->get(route('provider.statistics.index'))
            ->assertForbidden();
    }

    public function test_a_provider_without_a_subscription_is_refused(): void
    {
        $orphan = User::factory()->provider()->create();

        $this->actingAs($orphan)
            ->get(route('provider.statistics.index'))
            ->assertForbidden();
    }

    public function test_a_client_cannot_reach_the_page(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->get(route('provider.statistics.index'))
            ->assertForbidden();
    }

    public function test_the_navigation_only_offers_the_link_when_the_plan_allows_it(): void
    {
        $this->actingAs($this->provider)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('provider.statistics.index'));

        $this->provider->currentPlan()->update(['has_stats' => false]);

        $this->actingAs($this->provider)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('provider.statistics.index'));
    }

    public function test_a_provider_without_any_request_sees_the_empty_state(): void
    {
        $this->actingAs($this->provider)
            ->get(route('provider.statistics.index'))
            ->assertOk()
            ->assertSee(__('ui.stats.no_data'));
    }

    /**
     * Le taux d'acceptation se calcule sur les demandes auxquelles il a
     * répondu : une demande jamais ouverte ne dit rien de sa façon de
     * travailler.
     */
    public function test_the_acceptance_rate_ignores_requests_never_answered(): void
    {
        $this->requests(ServiceRequest::STATUS_COMPLETED, 3);
        $this->requests(ServiceRequest::STATUS_REFUSED, 1);
        $this->requests(ServiceRequest::STATUS_SENT, 10);

        $stats = $this->statistics();

        $this->assertSame(75, $stats['acceptance_rate']);
        $this->assertSame(14, $stats['requests_total']);
    }

    public function test_the_completion_rate_counts_finished_jobs_among_accepted_ones(): void
    {
        $this->requests(ServiceRequest::STATUS_COMPLETED, 2);
        $this->requests(ServiceRequest::STATUS_IN_PROGRESS, 2);

        $this->assertSame(50, $this->statistics()['completion_rate']);
    }

    public function test_rates_are_absent_rather_than_zero_when_nothing_can_be_computed(): void
    {
        $this->requests(ServiceRequest::STATUS_SENT, 3);

        $stats = $this->statistics();

        // Zéro pour cent d'acceptation serait un mensonge : il n'a répondu à
        // aucune demande, il n'en a refusé aucune non plus.
        $this->assertNull($stats['acceptance_rate']);
        $this->assertNull($stats['completion_rate']);
    }

    /**
     * La médiane plutôt que la moyenne : une seule demande oubliée trois
     * semaines fausserait une moyenne au point de la rendre inutilisable.
     */
    public function test_the_response_time_is_a_median_not_an_average(): void
    {
        foreach ([1, 2, 3, 400] as $hours) {
            ServiceRequest::factory()->create([
                'provider_id' => $this->provider->id,
                'status' => ServiceRequest::STATUS_ACCEPTED,
                'created_at' => now()->subHours($hours + 1),
                'responded_at' => now()->subHour(),
            ]);
        }

        // Médiane de 1, 2, 3 et 400 heures : 2,5 — une moyenne donnerait 101.
        $this->assertSame(2.5, $this->statistics()['median_response_hours']);
    }

    public function test_the_rating_breakdown_covers_every_score(): void
    {
        $this->reviewWithRating(5);
        $this->reviewWithRating(5);
        $this->reviewWithRating(3);

        $breakdown = $this->statistics()['rating_breakdown'];

        $this->assertSame([5, 4, 3, 2, 1], $breakdown->keys()->all());
        $this->assertSame(2, $breakdown[5]);
        $this->assertSame(1, $breakdown[3]);
        $this->assertSame(0, $breakdown[4]);
    }

    public function test_another_providers_activity_never_counts(): void
    {
        $other = User::factory()->provider()->create();
        ServiceRequest::factory()->count(5)->create([
            'provider_id' => $other->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $this->assertSame(0, $this->statistics()['requests_total']);
    }

    public function test_the_most_requested_services_come_first(): void
    {
        $popular = Service::factory()->create(['provider_id' => $this->provider->id, 'title' => 'Service tres demande']);
        $quiet = Service::factory()->create(['provider_id' => $this->provider->id, 'title' => 'Service discret']);

        ServiceRequest::factory()->count(4)->create([
            'provider_id' => $this->provider->id,
            'service_id' => $popular->id,
        ]);
        ServiceRequest::factory()->create([
            'provider_id' => $this->provider->id,
            'service_id' => $quiet->id,
        ]);

        $top = $this->statistics()['top_services'];

        $this->assertSame('Service tres demande', $top->first()['title']);
        $this->assertSame(4, $top->first()['requests']);
    }

    /**
     * @return array<string, mixed>
     */
    private function statistics(): array
    {
        return $this->app->make(ProviderStatisticsService::class)->forProvider($this->provider->fresh());
    }

    private function requests(string $status, int $count): void
    {
        ServiceRequest::factory()->count($count)->create([
            'provider_id' => $this->provider->id,
            'status' => $status,
        ]);
    }

    private function reviewWithRating(int $rating): void
    {
        $request = ServiceRequest::factory()->create([
            'provider_id' => $this->provider->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        Review::factory()->create([
            'request_id' => $request->id,
            'client_id' => $request->client_id,
            'provider_id' => $this->provider->id,
            'rating' => $rating,
        ]);
    }
}
