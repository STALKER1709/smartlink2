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
                // Le palier d'entrée : de quoi exister sur la plateforme, pas
                // de quoi en vivre. Un seul service, trois demandes lisibles
                // par mois, aucune mise en avant, aucun outil. Il n'est pas
                // là pour concurrencer les paliers payants mais pour qu'un
                // prestataire hésitant reste joignable.
                'code' => Plan::CODE_FREE,
                'price_xaf' => 0,
                'max_services' => 1,
                'max_monthly_requests' => 3,
                'is_featured' => false,
                'has_ai_writing' => false,
                'has_stats' => false,
                'sort_order' => 0,
            ],
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
