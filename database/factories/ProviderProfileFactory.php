<?php

namespace Database\Factories;

use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderProfile>
 */
class ProviderProfileFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda', 'Maroua']);

        return [
            'user_id' => User::factory()->provider(),
            'category_id' => ServiceCategory::factory(),
            'business_name' => fake()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => $city,
            'service_areas' => [$city],
            'opening_hours' => [
                'lundi_vendredi' => '08:00 - 18:00',
                'samedi' => '09:00 - 14:00',
            ],
            'contact_methods' => [
                'whatsapp' => fake()->numerify('6########'),
            ],
            'is_verified' => false,
            'rating_avg' => 0,
            'rating_count' => 0,
        ];
    }

    public function verified(): static
    {
        return $this->state(['is_verified' => true]);
    }
}
