<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers_reflect_the_user_role(): void
    {
        $client = User::factory()->client()->create();
        $provider = User::factory()->provider()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($client->isClient());
        $this->assertFalse($client->isProvider());

        $this->assertTrue($provider->isProvider());
        $this->assertFalse($provider->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isClient());
    }

    public function test_is_active_reflects_status(): void
    {
        $active = User::factory()->create();
        $suspended = User::factory()->suspended()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($suspended->isActive());
    }

    public function test_scope_of_role_filters_users(): void
    {
        User::factory(2)->client()->create();
        User::factory(3)->provider()->create();

        $this->assertSame(2, User::query()->ofRole(User::ROLE_CLIENT)->count());
        $this->assertSame(3, User::query()->ofRole(User::ROLE_PROVIDER)->count());
    }
}
