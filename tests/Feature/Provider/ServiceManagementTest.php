<?php

namespace Tests\Feature\Provider;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_provider_service_index(): void
    {
        $response = $this->get(route('provider.services.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_provider_service_index(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('provider.services.index'));

        $response->assertForbidden();
    }

    public function test_provider_can_view_their_services_index(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);

        $response = $this->actingAs($provider)->get(route('provider.services.index'));

        $response->assertOk();
        $response->assertViewHas('services', fn ($services) => $services->pluck('id')->contains($service->id));
    }

    /**
     * Les trois onglets de « Mes services », et leur définition d'« actif ».
     *
     * Une annonce travaille quand elle est publiée *et* disponible : un
     * prestataire qui décoche « disponible » le temps d'un déplacement
     * s'attend à la retrouver en pause, pas parmi les actives. Le filtre
     * s'écrit donc sur deux colonnes, et son onglet inverse sur un `orWhere` —
     * la forme exacte que SQLite pardonne et que PostgreSQL applique à la
     * lettre.
     */
    public function test_the_tabs_split_services_between_published_and_paused(): void
    {
        $provider = User::factory()->provider()->create();

        $actif = Service::factory()->create(['provider_id' => $provider->id]);
        $indisponible = Service::factory()->unavailable()->create(['provider_id' => $provider->id]);
        $depublie = Service::factory()->inactive()->create(['provider_id' => $provider->id]);

        $this->actingAs($provider)
            ->get(route('provider.services.index'))
            ->assertOk()
            ->assertViewHas('compteurs', fn (array $compteurs) => $compteurs === [
                'tous' => 3,
                'actifs' => 1,
                'pause' => 2,
            ]);

        $this->actingAs($provider)
            ->get(route('provider.services.index', ['statut' => 'actifs']))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->pluck('id')->all() === [$actif->id]);

        $this->actingAs($provider)
            ->get(route('provider.services.index', ['statut' => 'pause']))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->pluck('id')->sort()->values()->all()
                === collect([$indisponible->id, $depublie->id])->sort()->values()->all());
    }

    /** Un onglet inconnu retombe sur « tous » plutôt que de ne rien rendre. */
    public function test_an_unknown_tab_falls_back_to_all_services(): void
    {
        $provider = User::factory()->provider()->create();
        Service::factory()->count(2)->create(['provider_id' => $provider->id]);

        $this->actingAs($provider)
            ->get(route('provider.services.index', ['statut' => 'nimporte-quoi']))
            ->assertOk()
            ->assertViewHas('statut', 'tous')
            ->assertViewHas('services', fn ($services) => $services->count() === 2);
    }

    public function test_provider_can_create_a_service_with_images(): void
    {
        Storage::fake('public');

        $provider = User::factory()->provider()->create();
        $this->subscribeProvider($provider);
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($provider)->post(route('provider.services.store'), [
            'title' => 'Réparation de plomberie',
            'category_id' => $category->id,
            'description' => 'Intervention rapide et soignée à domicile.',
            'price_amount' => 5000,
            'price_unit' => 'par intervention',
            'city' => 'Douala',
            'is_available' => '1',
            'images' => [UploadedFile::fake()->image('service.jpg')],
        ]);

        $response->assertRedirect(route('provider.services.index'));
        $this->assertDatabaseHas('services', [
            'provider_id' => $provider->id,
            'title' => 'Réparation de plomberie',
            'status' => Service::STATUS_ACTIVE,
        ]);

        $service = Service::where('provider_id', $provider->id)->first();
        $this->assertSame(1, $service->images()->count());
        Storage::disk('public')->assertExists($service->images()->first()->path);
    }

    public function test_service_creation_requires_a_title(): void
    {
        $provider = User::factory()->provider()->create();
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($provider)->post(route('provider.services.store'), [
            'title' => '',
            'category_id' => $category->id,
            'description' => 'Description valide.',
            'city' => 'Douala',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_provider_cannot_edit_another_providers_service(): void
    {
        $owner = User::factory()->provider()->create();
        $intruder = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get(route('provider.services.edit', $service));

        $response->assertForbidden();
    }

    public function test_provider_can_update_their_service(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($provider)->put(route('provider.services.update', $service), [
            'title' => 'Titre mis à jour',
            'category_id' => $category->id,
            'description' => $service->description,
            'city' => $service->city,
            'is_available' => '0',
            'status' => Service::STATUS_INACTIVE,
        ]);

        $response->assertRedirect(route('provider.services.index'));
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'title' => 'Titre mis à jour',
            'status' => Service::STATUS_INACTIVE,
            'is_available' => false,
        ]);
    }

    public function test_provider_can_remove_an_image_when_updating_a_service(): void
    {
        Storage::fake('public');

        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);
        $image = ServiceImage::factory()->create([
            'service_id' => $service->id,
            'path' => UploadedFile::fake()->image('existing.jpg')->store('services', 'public'),
        ]);

        $response = $this->actingAs($provider)->put(route('provider.services.update', $service), [
            'title' => $service->title,
            'category_id' => $service->category_id,
            'description' => $service->description,
            'city' => $service->city,
            'status' => Service::STATUS_ACTIVE,
            'remove_images' => [$image->id],
        ]);

        $response->assertRedirect(route('provider.services.index'));
        $this->assertDatabaseMissing('service_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_provider_can_delete_their_service(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create(['provider_id' => $provider->id]);

        $response = $this->actingAs($provider)->delete(route('provider.services.destroy', $service));

        $response->assertRedirect(route('provider.services.index'));
        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }
}
