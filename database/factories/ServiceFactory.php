<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->randomElement([
            'Réparation de plomberie à domicile',
            'Installation électrique',
            'Ménage et nettoyage de maison',
            'Coiffure à domicile',
            'Couture sur mesure',
            'Réparation de meubles',
            'Peinture intérieure et extérieure',
            'Entretien de jardin',
            'Service de déménagement',
            'Réparation automobile',
            'Cours particuliers à domicile',
            'Reportage photo événementiel',
        ]);

        $city = fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam', 'Garoua', 'Bamenda', 'Maroua']);

        return [
            'provider_id' => User::factory()->provider(),
            'category_id' => ServiceCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('######'),
            'description' => fake()->paragraphs(2, true),
            'price_amount' => fake()->randomElement([2000, 5000, 7500, 10000, 15000, 25000, null]),
            'price_unit' => fake()->randomElement(['par heure', 'par intervention', 'par jour', null]),
            'location' => fake()->streetAddress(),
            'city' => $city,
            'is_available' => true,
            'availability_note' => null,
            'status' => Service::STATUS_ACTIVE,
            'views_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => Service::STATUS_INACTIVE]);
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }
}
