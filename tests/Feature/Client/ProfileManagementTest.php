<?php

namespace Tests\Feature\Client;

use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_client_profile_edit(): void
    {
        $response = $this->get(route('client.profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_provider_cannot_access_client_profile_edit(): void
    {
        $provider = User::factory()->provider()->create();

        $response = $this->actingAs($provider)->get(route('client.profile.edit'));

        $response->assertForbidden();
    }

    public function test_client_can_view_their_profile_edit_page(): void
    {
        $client = User::factory()->client()->create();
        $clientProfile = ClientProfile::factory()->create(['user_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('client.profile.edit'));

        $response->assertOk();
        $response->assertViewHas('clientProfile', fn ($profile) => $profile->id === $clientProfile->id);
    }

    public function test_client_can_update_their_profile(): void
    {
        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id]);

        $response = $this->actingAs($client)->put(route('client.profile.update'), [
            'first_name' => 'Aïcha',
            'last_name' => 'Mballa',
            'city' => 'Yaoundé',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $client->id,
            'first_name' => 'Aïcha',
            'last_name' => 'Mballa',
            'city' => 'Yaoundé',
        ]);
    }

    public function test_client_can_upload_a_photo(): void
    {
        Storage::fake('public');

        $client = User::factory()->client()->create();
        $clientProfile = ClientProfile::factory()->create(['user_id' => $client->id]);

        $response = $this->actingAs($client)->put(route('client.profile.update'), [
            'first_name' => $clientProfile->first_name,
            'last_name' => $clientProfile->last_name,
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertRedirect();
        $clientProfile->refresh();
        $this->assertNotNull($clientProfile->photo_path);
        Storage::disk('public')->assertExists($clientProfile->photo_path);
    }

    public function test_client_profile_update_requires_a_first_name(): void
    {
        $client = User::factory()->client()->create();
        ClientProfile::factory()->create(['user_id' => $client->id]);

        $response = $this->actingAs($client)->put(route('client.profile.update'), [
            'first_name' => '',
            'last_name' => 'Mballa',
        ]);

        $response->assertSessionHasErrors('first_name');
    }
}
