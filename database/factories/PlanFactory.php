<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => Plan::CODE_ESSENTIAL,
            'price_xaf' => 2500,
            'max_services' => 3,
            'max_monthly_requests' => 20,
            'is_featured' => false,
            'has_ai_writing' => true,
            'has_stats' => false,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'code' => Plan::CODE_FREE,
            'price_xaf' => 0,
            'max_services' => 1,
            'max_monthly_requests' => 3,
            'is_featured' => false,
            'has_ai_writing' => false,
            'has_stats' => false,
            'sort_order' => 0,
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn () => [
            'code' => Plan::CODE_PRO,
            'price_xaf' => 7500,
            'max_services' => null,
            'max_monthly_requests' => null,
            'is_featured' => true,
            'has_ai_writing' => true,
            'has_stats' => true,
            'sort_order' => 2,
        ]);
    }
}
