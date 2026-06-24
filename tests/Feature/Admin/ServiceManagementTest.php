<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_admin_service_list(): void
    {
        $response = $this->get(route('admin.services.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_the_admin_service_list(): void
    {
        $provider = User::factory()->provider()->create();

        $response = $this->actingAs($provider)->get(route('admin.services.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_the_service_list(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.services.index'));

        $response->assertOk();
        $response->assertViewHas('services', fn ($services) => $services->pluck('id')->contains($service->id));
    }

    public function test_admin_can_toggle_a_services_status(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['status' => Service::STATUS_ACTIVE]);

        $response = $this->actingAs($admin)->patch(route('admin.services.toggle-status', $service));

        $response->assertRedirect();
        $this->assertSame(Service::STATUS_INACTIVE, $service->refresh()->status);
    }

    public function test_admin_can_delete_a_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.services.destroy', $service));

        $response->assertRedirect();
        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    public function test_non_admin_cannot_toggle_a_services_status(): void
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($provider)->patch(route('admin.services.toggle-status', $service));

        $response->assertForbidden();
    }
}
