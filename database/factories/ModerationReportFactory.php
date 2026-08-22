<?php

namespace Database\Factories;

use App\Models\ModerationReport;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationReport>
 */
class ModerationReportFactory extends Factory
{
    protected $model = ModerationReport::class;

    public function definition(): array
    {
        return [
            'moderatable_type' => Service::class,
            'moderatable_id' => Service::factory(),
            'verdict' => ModerationReport::VERDICT_CLEAN,
            'categories' => [],
            'reason' => null,
            'model' => 'claude-haiku-4-5',
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn () => [
            'verdict' => ModerationReport::VERDICT_FLAGGED,
            'categories' => ['contact_hors_plateforme'],
            'reason' => 'Le texte invite à contacter le prestataire en dehors de la plateforme.',
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn () => ['reviewed_at' => now()]);
    }
}
