<?php

namespace Tests\Unit\Ai;

use App\Models\AiUsage;
use App\Models\User;
use App\Services\Ai\AiUsageRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageRecorderTest extends TestCase
{
    use RefreshDatabase;

    private AiUsageRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recorder = new AiUsageRecorder;
    }

    public function test_the_cost_follows_the_configured_per_million_rates(): void
    {
        config()->set('ai.pricing', [
            'modele-test' => ['input' => 5.0, 'output' => 25.0],
        ]);

        // 1 M de jetons d'entrée à 5 $, 100 000 de sortie à 25 $ le million.
        $this->assertSame(7.5, $this->recorder->cost('modele-test', 1_000_000, 100_000));
    }

    public function test_an_unknown_model_costs_zero_rather_than_blocking_the_platform(): void
    {
        $this->assertSame(0.0, $this->recorder->cost('modele-inconnu', 1_000_000, 1_000_000));
    }

    public function test_recording_stores_the_tokens_and_the_computed_cost(): void
    {
        config()->set('ai.pricing', [
            'modele-test' => ['input' => 5.0, 'output' => 25.0],
        ]);

        $user = User::factory()->client()->create();

        $usage = $this->recorder->record($user, AiUsage::FEATURE_CHAT, 'modele-test', 2_000, 400);

        $this->assertSame($user->id, $usage->user_id);
        $this->assertSame(2_000, $usage->input_tokens);
        $this->assertSame(400, $usage->output_tokens);
        $this->assertEqualsWithDelta(0.02, (float) $usage->cost_usd, 0.000001);
    }

    public function test_an_anonymous_call_is_still_recorded_against_the_budget(): void
    {
        $this->recorder->record(null, AiUsage::FEATURE_SEARCH, 'claude-haiku-4-5', 500, 50);

        $this->assertSame(1, AiUsage::whereNull('user_id')->count());
        $this->assertGreaterThan(0, $this->recorder->spentThisMonthUsd());
    }
}
