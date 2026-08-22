<?php

namespace Tests\Unit\Ai;

use App\Models\AiUsage;
use App\Models\User;
use App\Services\Ai\AiGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiGateTest extends TestCase
{
    use RefreshDatabase;

    private AiGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = $this->app->make(AiGate::class);
        config()->set('ai.driver', 'claude');
        config()->set('ai.api_key', 'cle-de-test');
        config()->set('ai.limits.require_authentication', true);
        config()->set('ai.limits.daily_messages_per_user', 20);
        config()->set('ai.limits.monthly_budget_usd', 50);
    }

    public function test_an_authenticated_user_is_allowed_when_everything_is_configured(): void
    {
        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide(User::factory()->client()->create()));
    }

    public function test_the_rule_driver_keeps_everything_off_the_api(): void
    {
        config()->set('ai.driver', 'rule');

        $this->assertSame(AiGate::REASON_DRIVER_OFF, $this->gate->decide(User::factory()->client()->create()));
    }

    public function test_a_missing_api_key_falls_back_instead_of_erroring(): void
    {
        config()->set('ai.api_key', '');

        $this->assertSame(AiGate::REASON_NO_KEY, $this->gate->decide(User::factory()->client()->create()));
    }

    public function test_a_guest_is_refused_when_authentication_is_required(): void
    {
        $this->assertSame(AiGate::REASON_GUEST, $this->gate->decide(null));
    }

    public function test_a_guest_is_allowed_when_authentication_is_not_required(): void
    {
        config()->set('ai.limits.require_authentication', false);

        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide(null));
    }

    public function test_the_daily_quota_closes_the_door_for_that_user_only(): void
    {
        config()->set('ai.limits.daily_messages_per_user', 3);

        $heavy = User::factory()->client()->create();
        $other = User::factory()->client()->create();

        AiUsage::factory()->count(3)->create([
            'user_id' => $heavy->id,
            'feature' => AiUsage::FEATURE_CHAT,
        ]);

        $this->assertSame(AiGate::REASON_DAILY_QUOTA, $this->gate->decide($heavy));
        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide($other));
    }

    public function test_yesterdays_messages_do_not_count_against_today(): void
    {
        config()->set('ai.limits.daily_messages_per_user', 3);
        $user = User::factory()->client()->create();

        AiUsage::factory()->count(3)->create([
            'user_id' => $user->id,
            'feature' => AiUsage::FEATURE_CHAT,
            'created_at' => now()->subDay(),
        ]);

        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide($user));
    }

    public function test_only_chat_messages_count_against_the_daily_quota(): void
    {
        config()->set('ai.limits.daily_messages_per_user', 2);
        $user = User::factory()->client()->create();

        AiUsage::factory()->count(5)->create([
            'user_id' => $user->id,
            'feature' => AiUsage::FEATURE_MODERATION,
        ]);

        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide($user));
    }

    public function test_the_monthly_budget_shuts_the_whole_platform_down_to_rules(): void
    {
        config()->set('ai.limits.monthly_budget_usd', 1);

        AiUsage::factory()->create(['cost_usd' => 1.5]);

        $this->assertSame(AiGate::REASON_BUDGET, $this->gate->decide(User::factory()->client()->create()));
    }

    public function test_last_months_spending_does_not_block_this_month(): void
    {
        config()->set('ai.limits.monthly_budget_usd', 1);

        AiUsage::factory()->create([
            'cost_usd' => 40,
            'created_at' => now()->subMonthNoOverflow()->startOfMonth(),
        ]);

        $this->assertSame(AiGate::REASON_ALLOWED, $this->gate->decide(User::factory()->client()->create()));
    }
}
