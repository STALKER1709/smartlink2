<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Plomberie', 'icon' => 'wrench'],
            ['name' => 'Électricité', 'icon' => 'bolt'],
            ['name' => 'Ménage', 'icon' => 'sparkles'],
            ['name' => 'Coiffure', 'icon' => 'scissors'],
            ['name' => 'Couture', 'icon' => 'needle'],
            ['name' => 'Menuiserie', 'icon' => 'hammer'],
            ['name' => 'Peinture', 'icon' => 'paint-brush'],
            ['name' => 'Jardinage', 'icon' => 'leaf'],
            ['name' => 'Déménagement', 'icon' => 'truck'],
            ['name' => 'Mécanique auto', 'icon' => 'car'],
            ['name' => 'Informatique', 'icon' => 'computer-desktop'],
            ['name' => 'Cours particuliers', 'icon' => 'book-open'],
            ['name' => 'Photographie', 'icon' => 'camera'],
            ['name' => 'Traiteur', 'icon' => 'cake'],
            ['name' => 'Maçonnerie', 'icon' => 'building-office'],
        ];

        foreach ($categories as $category) {
            ServiceCategory::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'description' => "Trouvez les meilleurs prestataires en {$category['name']} près de chez vous.",
                    'is_active' => true,
                ]
            );
        }
    }
}
