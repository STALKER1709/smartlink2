<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_provider_profile_edit(): void
    {
        $response = $this->get(route('provider.profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_client_cannot_access_provider_profile_edit(): void
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('provider.profile.edit'));

        $response->assertForbidden();
    }

    public function test_provider_can_view_their_profile_edit_page(): void
    {
        $provider = User::factory()->provider()->create();
        $providerProfile = ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $response = $this->actingAs($provider)->get(route('provider.profile.edit'));

        $response->assertOk();
        $response->assertViewHas('providerProfile', fn ($profile) => $profile->id === $providerProfile->id);
    }

    public function test_provider_can_update_their_profile(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $category = ServiceCategory::factory()->create();

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => 'Atelier Excellence',
            'category_id' => $category->id,
            'city' => 'Douala',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('provider_profiles', [
            'user_id' => $provider->id,
            'business_name' => 'Atelier Excellence',
            'city' => 'Douala',
        ]);
    }

    public function test_provider_can_upload_a_logo(): void
    {
        Storage::fake('public');

        $provider = User::factory()->provider()->create();
        $providerProfile = ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => $providerProfile->business_name,
            'city' => $providerProfile->city,
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ]);

        $response->assertRedirect();
        $providerProfile->refresh();
        $this->assertNotNull($providerProfile->logo_path);
        Storage::disk('public')->assertExists($providerProfile->logo_path);
    }

    public function test_provider_profile_update_requires_a_business_name(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => '',
            'city' => 'Douala',
        ]);

        $response->assertSessionHasErrors('business_name');
    }
}
