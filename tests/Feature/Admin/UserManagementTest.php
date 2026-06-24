<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_admin_user_list(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_the_admin_user_list(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_the_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($client->id));
    }

    public function test_admin_can_suspend_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.suspend', $client));

        $response->assertRedirect();
        $this->assertSame(User::STATUS_SUSPENDED, $client->refresh()->status);
    }

    public function test_admin_can_reactivate_a_suspended_user(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->client()->suspended()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.reactivate', $client));

        $response->assertRedirect();
        $this->assertSame(User::STATUS_ACTIVE, $client->refresh()->status);
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.suspend', $admin));

        $response->assertForbidden();
        $this->assertSame(User::STATUS_ACTIVE, $admin->refresh()->status);
    }

    public function test_non_admin_cannot_suspend_a_user(): void
    {
        $client = User::factory()->client()->create();
        $otherClient = User::factory()->client()->create();

        $response = $this->actingAs($client)->post(route('admin.users.suspend', $otherClient));

        $response->assertForbidden();
    }
}
