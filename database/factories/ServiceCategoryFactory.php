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
        $pool = [
            'Plomberie', 'Électricité', 'Ménage', 'Coiffure', 'Couture',
            'Menuiserie', 'Peinture', 'Jardinage', 'Déménagement', 'Mécanique auto',
            'Informatique', 'Cours particuliers', 'Photographie', 'Traiteur', 'Maçonnerie',
        ];

        // Deux sources de collision à couvrir. D'abord la création par lot :
        // toutes les définitions sont évaluées avant la moindre insertion, donc
        // seule l'unicité en mémoire de Faker les sépare. Ensuite les noms posés
        // par surcharge dans un test, que Faker ignore et que seule la base
        // connaît. Le nom doit survivre aux deux.
        try {
            $name = fake()->unique()->randomElement($pool);
        } catch (\OverflowException) {
            $name = 'Catégorie '.fake()->unique()->numberBetween(1, 1_000_000);
        }

        if (ServiceCategory::where('name', $name)->exists()) {
            $name .= ' '.fake()->unique()->numberBetween(1, 1_000_000);
        }

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => null,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Le slug doit suivre le nom retenu, pas celui tiré au sort : sans cela,
     * une catégorie créée avec un nom imposé garde le slug d'un autre nom, et
     * le tirage suivant entre en collision avec ce slug orphelin.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ServiceCategory $category): void {
            $category->slug = Str::slug($category->name);
        });
    }
}
