<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_sees_client_dashboard(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.client');
    }

    public function test_provider_sees_provider_dashboard(): void
    {
        $provider = User::factory()->provider()->create();

        $response = $this->actingAs($provider)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.provider');
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_suspended_user_is_logged_out_on_any_request(): void
    {
        $suspended = User::factory()->suspended()->create();

        $response = $this->actingAs($suspended)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
