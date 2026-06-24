<?php

namespace Database\Factories;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientProfile>
 */
class ClientProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->client(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'city' => fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda', 'Maroua']),
            'location' => fake()->streetAddress(),
            'preferences' => [],
        ];
    }
}
