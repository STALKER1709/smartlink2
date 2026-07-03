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
            // Artisanat & bâtiment
            ['name' => 'Plomberie', 'icon' => 'wrench'],
            ['name' => 'Électricité', 'icon' => 'bolt'],
            ['name' => 'Maçonnerie', 'icon' => 'building-office'],
            ['name' => 'Menuiserie', 'icon' => 'hammer'],
            ['name' => 'Peinture', 'icon' => 'paint-brush'],
            ['name' => 'Carrelage', 'icon' => 'squares-2x2'],
            ['name' => 'Soudure & ferronnerie', 'icon' => 'fire'],
            // Maison & confort
            ['name' => 'Ménage', 'icon' => 'sparkles'],
            ['name' => 'Jardinage', 'icon' => 'leaf'],
            ['name' => 'Gardiennage', 'icon' => 'shield-check'],
            ['name' => 'Générateur & énergie solaire', 'icon' => 'lightning-bolt'],
            ['name' => 'Climatisation & réfrigération', 'icon' => 'sun'],
            // Transport & logistique
            ['name' => 'Moto-taxi', 'icon' => 'truck'],
            ['name' => 'Déménagement', 'icon' => 'truck'],
            ['name' => 'Livraison d\'eau', 'icon' => 'beaker'],
            ['name' => 'Location de véhicules', 'icon' => 'car'],
            // Beauté & bien-être
            ['name' => 'Coiffure', 'icon' => 'scissors'],
            ['name' => 'Couture & stylisme', 'icon' => 'needle'],
            ['name' => 'Massage & soins', 'icon' => 'hand'],
            ['name' => 'Maquillage & ongles', 'icon' => 'sparkles'],
            // Technologie
            ['name' => 'Informatique & réparation', 'icon' => 'computer-desktop'],
            ['name' => 'Réparation téléphone', 'icon' => 'device-mobile'],
            ['name' => 'Installation TV & antenne', 'icon' => 'tv'],
            // Alimentation & événements
            ['name' => 'Traiteur & cuisine', 'icon' => 'cake'],
            ['name' => 'Vente de vivres', 'icon' => 'shopping-bag'],
            ['name' => 'Photographie & vidéo', 'icon' => 'camera'],
            ['name' => 'Animation & DJ', 'icon' => 'musical-note'],
            // Santé & éducation
            ['name' => 'Mécanique auto & moto', 'icon' => 'car'],
            ['name' => 'Cours particuliers', 'icon' => 'book-open'],
            ['name' => 'Garde d\'enfants', 'icon' => 'user-group'],
            ['name' => 'Aide à domicile', 'icon' => 'home'],
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
