<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportPhotosTest extends TestCase
{
    use RefreshDatabase;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossier = base_path('design/photos');
        File::ensureDirectoryExists($this->dossier);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        File::delete(File::glob($this->dossier.'/*-test.jpg'));

        parent::tearDown();
    }

    private function service(string $categorie): Service
    {
        return Service::factory()->create([
            'provider_id' => User::factory()->provider()->create()->id,
            'category_id' => ServiceCategory::factory()->create(['name' => $categorie])->id,
        ]);
    }

    private function deposer(string $nom): void
    {
        // Un JPEG minimal : la commande ne lit pas le contenu, seulement le nom.
        File::put($this->dossier.'/'.$nom, base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0a'
            .'HBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAA'
            .'AAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q=='
        ));
    }

    /**
     * Une photographie remplace l'illustration de remplissage : deux
     * couvertures laisseraient le hasard décider laquelle paraît.
     */
    public function test_a_photo_replaces_the_generated_cover(): void
    {
        $service = $this->service('Plomberie');
        ServiceImage::create(['service_id' => $service->id, 'path' => 'services/demo/plomberie-0.jpg', 'position' => 0]);

        $this->deposer('plomberie-test.jpg');

        $this->artisan('photos:import')->assertSuccessful();

        $service->refresh();
        $this->assertSame(1, $service->images()->count());
        $this->assertStringStartsWith('services/photos/', $service->images()->first()->path);
        Storage::disk('public')->assertExists('services/photos/plomberie-test.jpg');
    }

    /**
     * Les photos qu'un prestataire a déposées lui-même ne sont jamais
     * retirées : la commande sert le remplissage, pas son travail.
     */
    public function test_a_providers_own_photo_is_kept(): void
    {
        $service = $this->service('Plomberie');
        ServiceImage::create(['service_id' => $service->id, 'path' => 'services/9/atelier.jpg', 'position' => 1]);

        $this->deposer('plomberie-test.jpg');
        $this->artisan('photos:import')->assertSuccessful();

        $this->assertTrue($service->images()->where('path', 'services/9/atelier.jpg')->exists());
    }

    public function test_an_unknown_key_is_reported_and_skipped(): void
    {
        $this->service('Plomberie');
        $this->deposer('zzzinconnu-test.jpg');

        $this->artisan('photos:import')
            ->expectsOutputToContain('clé « zzzinconnu » inconnue')
            ->assertSuccessful();

        $this->assertDatabaseCount('service_images', 0);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->service('Plomberie');
        $this->deposer('plomberie-test.jpg');

        $this->artisan('photos:import', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('service_images', 0);
        Storage::disk('public')->assertMissing('services/photos/plomberie-test.jpg');
    }
}
