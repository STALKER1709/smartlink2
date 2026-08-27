<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\ServiceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les photographies distantes.
 *
 * Le défaut qu'on ne verrait pas : `REMOTE_IMAGES=false` posé sur
 * l'hébergement, et une vue qui continue d'appeler un tiers parce qu'elle a
 * gardé l'URL en dur. Rien ne casse — chaque visiteur paie simplement une
 * requête vers un hôte que l'exploitant croyait débranché, et lui envoie son
 * adresse IP.
 */
class RemoteImageryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_switch_silences_every_call_to_the_outside(): void
    {
        config()->set('imagery.enabled', false);

        $categorie = ServiceCategory::factory()->create(['name' => 'Plomberie']);
        Service::factory()->create(['category_id' => $categorie->id]);

        $this->assertNull(image_categorie('Plomberie'));
        $this->assertNull(image_distante('cta'));

        $this->get(route('home'))->assertOk()->assertDontSee('loremflickr');
        $this->get(route('services.index'))->assertOk()->assertDontSee('loremflickr');
    }

    public function test_an_unknown_category_keeps_its_pictogram_rather_than_a_wrong_photo(): void
    {
        config()->set('imagery.enabled', true);

        $this->assertNull(image_categorie('Métier qui n\'existe pas'));
        $this->assertNull(image_categorie(null));
    }

    public function test_every_seeded_category_carries_a_photo(): void
    {
        // Une seule catégorie manquante fait une grille bancale : des vignettes
        // photographiques à côté de vignettes à pictogramme, sur la même
        // rangée. La table des photos et celle des catégories doivent couvrir
        // le même ensemble.
        $this->seed(ServiceCategorySeeder::class);

        $sans = ServiceCategory::query()
            ->pluck('name')
            ->reject(fn (string $nom) => image_categorie($nom) !== null)
            ->values()
            ->all();

        $this->assertSame([], $sans, 'Catégories sans photographie distante : '.implode(', ', $sans));
    }
}
