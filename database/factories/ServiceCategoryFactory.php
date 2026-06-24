<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Plomberie', 'Électricité', 'Ménage', 'Coiffure', 'Couture',
            'Menuiserie', 'Peinture', 'Jardinage', 'Déménagement', 'Mécanique auto',
            'Informatique', 'Cours particuliers', 'Photographie', 'Traiteur', 'Maçonnerie',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
