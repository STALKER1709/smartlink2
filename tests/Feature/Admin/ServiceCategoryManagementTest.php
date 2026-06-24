<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_admin_category_list(): void
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_the_admin_category_list(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_the_category_list(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertViewHas('categories', fn ($categories) => $categories->pluck('id')->contains($category->id));
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Plomberie',
            'description' => 'Travaux de plomberie générale.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'name' => 'Plomberie',
            'slug' => 'plomberie',
            'is_active' => true,
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        ServiceCategory::factory()->create(['name' => 'Plomberie']);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Plomberie',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Ménage']);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Ménage et nettoyage',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Ménage et nettoyage',
            'slug' => 'menage-et-nettoyage',
        ]);
    }

    public function test_updating_a_category_can_keep_its_own_name(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Coiffure']);

        $response = $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Coiffure',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionDoesntHaveErrors('name');
    }

    public function test_admin_can_delete_a_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect();
        $this->assertSoftDeleted('service_categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_create_a_category(): void
    {
        $provider = User::factory()->provider()->create();

        $response = $this->actingAs($provider)->post(route('admin.categories.store'), [
            'name' => 'Catégorie interdite',
        ]);

        $response->assertForbidden();
    }
}
