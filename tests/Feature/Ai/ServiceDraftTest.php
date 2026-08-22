<?php

namespace Tests\Feature\Ai;

use App\Models\Plan;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Ai\ServiceDraftWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.driver', 'claude');
        config()->set('ai.api_key', 'cle-de-test');

        $this->provider = User::factory()->provider()->create();
        $this->subscribeProvider($this->provider, Plan::CODE_PRO);
    }

    public function test_a_subscribed_provider_gets_a_proposed_title_and_description(): void
    {
        $this->fakeWriter(['title' => 'Plombier à Douala', 'description' => 'Je répare les fuites…']);

        $this->actingAs($this->provider)
            ->postJson(route('provider.services.draft'), [
                'notes' => 'je repare les fuites et je pose des chauffe-eau',
                'category_id' => ServiceCategory::factory()->create()->id,
                'city' => 'Douala',
            ])
            ->assertOk()
            ->assertJson(['title' => 'Plombier à Douala', 'description' => 'Je répare les fuites…']);
    }

    public function test_a_plan_without_assisted_writing_is_told_so(): void
    {
        $plan = $this->provider->currentPlan();
        $plan->update(['has_ai_writing' => false]);

        $this->actingAs($this->provider)
            ->postJson(route('provider.services.draft'), ['notes' => 'je repare les fuites'])
            ->assertStatus(422)
            ->assertJson(['message' => __('ui.draft.plan_required')]);
    }

    public function test_a_provider_without_a_subscription_cannot_use_it(): void
    {
        $orphan = User::factory()->provider()->create();

        $this->actingAs($orphan)
            ->postJson(route('provider.services.draft'), ['notes' => 'je repare les fuites'])
            ->assertStatus(422);
    }

    public function test_a_failed_generation_reports_a_usable_message(): void
    {
        $this->fakeWriter(null);

        $this->actingAs($this->provider)
            ->postJson(route('provider.services.draft'), ['notes' => 'je repare les fuites'])
            ->assertStatus(422)
            ->assertJson(['message' => __('ui.draft.failed')]);
    }

    public function test_notes_are_required_and_bounded(): void
    {
        $this->actingAs($this->provider)
            ->postJson(route('provider.services.draft'), ['notes' => ''])
            ->assertJsonValidationErrors('notes');

        $this->actingAs($this->provider)
            ->postJson(route('provider.services.draft'), ['notes' => str_repeat('a', 601)])
            ->assertJsonValidationErrors('notes');
    }

    public function test_a_client_cannot_reach_the_endpoint(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->postJson(route('provider.services.draft'), ['notes' => 'bonjour'])
            ->assertForbidden();
    }

    public function test_nothing_is_attempted_when_the_ai_is_off(): void
    {
        config()->set('ai.driver', 'rule');

        // L'écrivain réel est en place : si le garde-fou ne coupait pas, le
        // test échouerait faute de clé plutôt que de renvoyer null.
        $writer = $this->app->make(ServiceDraftWriter::class);

        $this->assertNull($writer->draft($this->provider, 'je repare les fuites', null, null));
        $this->assertFalse($writer->isAvailableFor($this->provider));
    }

    public function test_the_form_only_offers_the_box_when_the_plan_allows_it(): void
    {
        $this->actingAs($this->provider)
            ->get(route('provider.services.create'))
            ->assertOk()
            ->assertSee(__('ui.draft.label'));

        $this->provider->currentPlan()->update(['has_ai_writing' => false]);

        $this->actingAs($this->provider)
            ->get(route('provider.services.create'))
            ->assertOk()
            ->assertDontSee(__('ui.draft.label'));
    }

    /**
     * @param  array{title: string, description: string}|null  $draft
     */
    private function fakeWriter(?array $draft): void
    {
        $fake = new class($draft) extends ServiceDraftWriter
        {
            public function __construct(private readonly ?array $draft)
            {
                // Ce double ne parle à aucune API.
            }

            public function draft(User $provider, string $notes, ?ServiceCategory $category, ?string $city): ?array
            {
                return $this->draft;
            }

            public function isOpenToPlan(User $provider): bool
            {
                return $provider->currentPlan()?->has_ai_writing === true;
            }

            public function isAvailableFor(User $provider): bool
            {
                return $this->isOpenToPlan($provider);
            }
        };

        $this->app->instance(ServiceDraftWriter::class, $fake);
    }
}
