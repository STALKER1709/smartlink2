<?php

namespace Tests\Feature\Provider;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('provider.reviews.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_provider_reviews(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('provider.reviews.index'));

        $response->assertForbidden();
    }

    public function test_provider_sees_only_their_own_reviews(): void
    {
        $provider = User::factory()->provider()->create();
        $otherProvider = User::factory()->provider()->create();

        $mine = Review::factory()->create(['provider_id' => $provider->id, 'comment' => 'Excellent travail']);
        Review::factory()->create(['provider_id' => $otherProvider->id, 'comment' => 'Pas pour moi']);

        $response = $this->actingAs($provider)->get(route('provider.reviews.index'));

        $response->assertOk();
        $response->assertViewIs('provider.reviews.index');
        $response->assertSee('Excellent travail');
        $response->assertDontSee('Pas pour moi');
    }
}
