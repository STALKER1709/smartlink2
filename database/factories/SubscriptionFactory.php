<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->provider(),
            // Les paliers sont un ensemble fermé à code unique : on réutilise
            // celui qui existe déjà plutôt que d'en créer un par abonnement.
            'plan_id' => fn () => Plan::query()->orderBy('id')->value('id')
                ?? Plan::factory()->create()->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_TRIALING,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Subscription::STATUS_EXPIRED,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDay(),
        ]);
    }
}
