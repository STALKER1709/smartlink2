<?php

namespace Tests\Feature\Ai;

use App\Contracts\ChatbotProvider;
use App\Models\AiUsage;
use App\Models\User;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.driver', 'claude');
        config()->set('ai.api_key', 'cle-de-test');
        config()->set('ai.limits.require_authentication', true);
        config()->set('ai.limits.daily_messages_per_user', 20);
        config()->set('ai.limits.monthly_budget_usd', 50);
    }

    public function test_an_authenticated_user_reaches_the_ai(): void
    {
        $this->fakeAi('Réponse produite par l\'IA.');

        $this->actingAs(User::factory()->client()->create())
            ->postJson(route('chatbot.ask'), ['message' => 'Comment ça marche ?'])
            ->assertOk()
            ->assertJson(['reply' => 'Réponse produite par l\'IA.']);
    }

    public function test_a_guest_stays_on_the_rule_mode(): void
    {
        $this->fakeAi('Cette réponse ne doit jamais sortir.');

        $reply = $this->postJson(route('chatbot.ask'), ['message' => 'Bonjour'])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_a_failing_ai_call_falls_back_to_the_rules_instead_of_erroring(): void
    {
        $this->app->bind(ChatbotProvider::class, fn () => new class implements ChatbotProvider
        {
            public function respond(string $message, array $history = [], ?User $user = null): string
            {
                throw new \RuntimeException('API injoignable');
            }
        });

        $reply = $this->actingAs(User::factory()->client()->create())
            ->postJson(route('chatbot.ask'), ['message' => 'Bonjour'])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_beyond_the_daily_quota_the_user_falls_back_to_the_rules(): void
    {
        config()->set('ai.limits.daily_messages_per_user', 2);
        $this->fakeAi('Cette réponse ne doit jamais sortir.');

        $user = User::factory()->client()->create();
        AiUsage::factory()->count(2)->create([
            'user_id' => $user->id,
            'feature' => AiUsage::FEATURE_CHAT,
        ]);

        $reply = $this->actingAs($user)
            ->postJson(route('chatbot.ask'), ['message' => 'Bonjour'])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_beyond_the_monthly_budget_everyone_falls_back_to_the_rules(): void
    {
        config()->set('ai.limits.monthly_budget_usd', 1);
        AiUsage::factory()->create(['cost_usd' => 2]);
        $this->fakeAi('Cette réponse ne doit jamais sortir.');

        $reply = $this->actingAs(User::factory()->client()->create())
            ->postJson(route('chatbot.ask'), ['message' => 'Bonjour'])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_the_service_hands_the_user_to_the_provider_for_metering(): void
    {
        $spy = new class implements ChatbotProvider
        {
            public ?int $seenUserId = null;

            public function respond(string $message, array $history = [], ?User $user = null): string
            {
                $this->seenUserId = $user?->id;

                return 'ok';
            }
        };

        $this->app->instance(ChatbotProvider::class, $spy);

        $user = User::factory()->client()->create();
        $this->app->make(ChatbotService::class)->ask('Bonjour', [], $user);

        $this->assertSame($user->id, $spy->seenUserId);
    }

    private function fakeAi(string $reply): void
    {
        $this->app->bind(ChatbotProvider::class, fn () => new class($reply) implements ChatbotProvider
        {
            public function __construct(private readonly string $reply) {}

            public function respond(string $message, array $history = [], ?User $user = null): string
            {
                return $this->reply;
            }
        });
    }
}
