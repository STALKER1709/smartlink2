<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => Plan::CODE_ESSENTIAL,
                'price_xaf' => 2500,
                'max_services' => 3,
                'max_monthly_requests' => 20,
                'is_featured' => false,
                'has_ai_writing' => true,
                'has_stats' => false,
                'sort_order' => 1,
            ],
            [
                'code' => Plan::CODE_PRO,
                'price_xaf' => 7500,
                'max_services' => null,
                'max_monthly_requests' => null,
                'is_featured' => true,
                'has_ai_writing' => true,
                'has_stats' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['code' => $plan['code']], $plan + ['is_active' => true]);
        }
    }
}
