<?php

namespace Tests\Feature\Ai;

use App\Jobs\ModerateContent;
use App\Models\ModerationReport;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Ai\ContentModerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_a_service_queues_a_review(): void
    {
        Queue::fake();

        $service = Service::factory()->create();

        Queue::assertPushed(ModerateContent::class);
        $this->assertNotNull($service->id);
    }

    public function test_changing_only_the_price_does_not_queue_a_new_review(): void
    {
        $service = Service::factory()->create();

        Queue::fake();
        $service->update(['price_amount' => 12000]);

        Queue::assertNothingPushed();
    }

    public function test_rewriting_the_description_queues_a_new_review(): void
    {
        $service = Service::factory()->create();

        Queue::fake();
        $service->update(['description' => 'Un texte entièrement réécrit par le prestataire.']);

        Queue::assertPushed(ModerateContent::class);
    }

    public function test_leaving_a_review_queues_a_moderation_pass(): void
    {
        Queue::fake();

        Review::factory()->create();

        Queue::assertPushed(ModerateContent::class);
    }

    public function test_a_flagged_verdict_is_stored_against_the_content(): void
    {
        $service = Service::factory()->create();

        $this->fakeModerator(fn ($content) => ModerationReport::factory()->flagged()->create([
            'moderatable_type' => $content->getMorphClass(),
            'moderatable_id' => $content->getKey(),
        ]));

        (new ModerateContent($service))->handle($this->app->make(ContentModerator::class));

        $report = ModerationReport::firstOrFail();
        $this->assertTrue($report->isFlagged());
        $this->assertTrue($report->moderatable->is($service));
    }

    public function test_the_ai_never_removes_the_content_it_flags(): void
    {
        $service = Service::factory()->create();
        ModerationReport::factory()->flagged()->create([
            'moderatable_type' => $service->getMorphClass(),
            'moderatable_id' => $service->id,
        ]);

        // Le service reste publié et visible : seul un administrateur décide.
        $this->assertDatabaseHas('services', ['id' => $service->id]);
        $this->get(route('services.show', $service))->assertOk();
    }

    public function test_nothing_is_attempted_when_the_ai_is_off(): void
    {
        config()->set('ai.driver', 'rule');
        $service = Service::factory()->create();

        $moderator = $this->app->make(ContentModerator::class);

        $this->assertNull($moderator->review($service, 'un texte quelconque'));
        $this->assertSame(0, ModerationReport::count());
    }

    public function test_an_admin_sees_the_pending_queue(): void
    {
        $service = Service::factory()->create(['title' => 'Annonce douteuse']);
        ModerationReport::factory()->flagged()->create([
            'moderatable_type' => $service->getMorphClass(),
            'moderatable_id' => $service->id,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.moderation.index'))
            ->assertOk()
            ->assertSee('Annonce douteuse')
            ->assertSee(__('ui.moderation.categories.contact_hors_plateforme'));
    }

    public function test_clean_and_already_reviewed_reports_stay_out_of_the_queue(): void
    {
        $clean = Service::factory()->create(['title' => 'Annonce saine']);
        ModerationReport::factory()->create([
            'moderatable_type' => $clean->getMorphClass(),
            'moderatable_id' => $clean->id,
        ]);

        $handled = Service::factory()->create(['title' => 'Annonce deja traitee']);
        ModerationReport::factory()->flagged()->reviewed()->create([
            'moderatable_type' => $handled->getMorphClass(),
            'moderatable_id' => $handled->id,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.moderation.index'))
            ->assertOk()
            ->assertDontSee('Annonce saine')
            ->assertDontSee('Annonce deja traitee');
    }

    public function test_dismissing_a_flag_leaves_the_content_untouched(): void
    {
        $service = Service::factory()->create();
        $report = ModerationReport::factory()->flagged()->create([
            'moderatable_type' => $service->getMorphClass(),
            'moderatable_id' => $service->id,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.moderation.dismiss', $report))
            ->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertDatabaseHas('services', ['id' => $service->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'moderation.dismissed']);
    }

    public function test_a_provider_cannot_reach_the_moderation_queue(): void
    {
        $provider = User::factory()->provider()->create();
        $this->subscribeProvider($provider, Plan::CODE_PRO);

        $this->actingAs($provider)
            ->get(route('admin.moderation.index'))
            ->assertForbidden();
    }

    public function test_a_review_without_a_comment_is_not_sent_for_moderation(): void
    {
        $request = ServiceRequest::factory()->create(['status' => ServiceRequest::STATUS_COMPLETED]);
        $review = Review::factory()->create([
            'request_id' => $request->id,
            'client_id' => $request->client_id,
            'provider_id' => $request->provider_id,
            'comment' => null,
        ]);

        $moderator = $this->app->make(ContentModerator::class);
        (new ModerateContent($review))->handle($moderator);

        $this->assertSame(0, ModerationReport::count());
    }

    private function fakeModerator(callable $handler): void
    {
        $fake = new class($handler) extends ContentModerator
        {
            public function __construct(private $handler)
            {
                // Ce double ne parle à aucune API.
            }

            public function review(Model $content, string $text): ?ModerationReport
            {
                return ($this->handler)($content);
            }
        };

        $this->app->instance(ContentModerator::class, $fake);
    }
}
