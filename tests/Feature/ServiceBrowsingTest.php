<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_active_services(): void
    {
        Service::factory(3)->create();
        Service::factory()->inactive()->create();

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertViewHas('services', fn ($services) => $services->total() === 3);
    }

    public function test_services_can_be_filtered_by_category_and_city(): void
    {
        $category = ServiceCategory::factory()->create();
        $match = Service::factory()->create(['category_id' => $category->id, 'city' => 'Douala']);
        Service::factory()->create(['city' => 'Yaoundé']);

        $response = $this->get(route('services.index', [
            'category_id' => $category->id,
            'city' => 'Douala',
        ]));

        $response->assertViewHas('services', function ($services) use ($match) {
            return $services->total() === 1 && $services->first()->is($match);
        });
    }

    public function test_guest_can_view_an_active_service(): void
    {
        $service = Service::factory()->create();

        $response = $this->get(route('services.show', $service));

        $response->assertOk();
        $response->assertViewHas('service', fn ($viewed) => $viewed->is($service));
    }

    public function test_related_services_share_the_category_and_exclude_itself(): void
    {
        $category = ServiceCategory::factory()->create();
        $service = Service::factory()->create(['category_id' => $category->id]);
        $related = Service::factory()->create(['category_id' => $category->id]);
        Service::factory()->create();

        $response = $this->get(route('services.show', $service));

        $response->assertViewHas('relatedServices', function ($services) use ($service, $related) {
            return ! $services->pluck('id')->contains($service->id)
                && $services->pluck('id')->contains($related->id);
        });
    }

    public function test_unknown_service_slug_returns_404(): void
    {
        $response = $this->get('/services/inexistant');

        $response->assertNotFound();
    }
}
