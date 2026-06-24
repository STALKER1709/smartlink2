<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => ServiceRequest::factory()->completed(),
            'client_id' => User::factory()->client(),
            'provider_id' => User::factory()->provider(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->optional(0.8)->paragraph(),
            'is_visible' => true,
        ];
    }
}
