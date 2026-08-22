<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_help_center(): void
    {
        $response = $this->get(route('help.index'));

        $response->assertOk();
        $response->assertViewIs('help.index');
    }

    public function test_authenticated_user_can_view_the_help_center(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('help.index'));

        $response->assertOk();
    }
}
