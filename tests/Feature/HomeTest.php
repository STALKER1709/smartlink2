<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_for_guest(): void
    {
        ServiceCategory::factory(3)->create();
        Service::factory(2)->create();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('home');
        $response->assertViewHas('categories');
        $response->assertViewHas('recentServices');
    }

    public function test_home_page_only_lists_active_available_services(): void
    {
        $visible = Service::factory()->create();
        Service::factory()->inactive()->create();
        Service::factory()->unavailable()->create();

        $response = $this->get(route('home'));

        $response->assertViewHas('recentServices', function ($services) use ($visible) {
            return $services->pluck('id')->contains($visible->id) && $services->count() === 1;
        });
    }
}
