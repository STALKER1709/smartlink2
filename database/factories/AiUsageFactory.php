<?php

namespace Database\Factories;

use App\Models\AiUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiUsage>
 */
class AiUsageFactory extends Factory
{
    protected $model = AiUsage::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'feature' => AiUsage::FEATURE_CHAT,
            'model' => 'claude-opus-5',
            'input_tokens' => 1200,
            'output_tokens' => 180,
            'cost_usd' => 0.0105,
        ];
    }
}
