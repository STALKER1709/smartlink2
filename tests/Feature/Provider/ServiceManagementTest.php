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
