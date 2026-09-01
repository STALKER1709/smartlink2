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

    /**
     * Le formulaire ouvre toujours une ligne vide sous « Zones d'intervention »
     * et sous « Moyens de contact », pour qu'il y ait un champ où écrire. Le
     * prestataire qui n'en veut pas la laisse vide — et le navigateur poste
     * alors `service_areas[] = ''`.
     *
     * `ConvertEmptyStringsToNull`, actif par défaut, en fait un null, que la
     * règle `string` refuse : « Le champ service_areas.0 doit être une chaîne
     * de caractères. » Aucun prestataire ne pouvait enregistrer son profil
     * sans remplir deux champs annoncés facultatifs, et le message ne
     * désignait aucun champ visible à l'écran.
     */
    public function test_provider_can_leave_the_optional_repeaters_empty(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => 'Le Monstre',
            'city' => 'Bertoua',
            'service_areas' => [''],
            'contact_methods' => [''],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $profil = $provider->fresh()->providerProfile;
        $this->assertSame([], $profil->service_areas);
        $this->assertSame([], $profil->contact_methods);
    }

    /**
     * Une ligne laissée vide au milieu de deux autres ne doit pas non plus
     * bloquer, ni ressortir comme une zone d'intervention sans nom.
     */
    public function test_blank_rows_are_dropped_and_the_others_kept(): void
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);

        $response = $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => 'Le Monstre',
            'city' => 'Bertoua',
            'service_areas' => ['Bastos', '', 'Akwa'],
            'contact_methods' => ['', '+237 6XX XXX XXX'],
        ]);

        $response->assertSessionHasNoErrors();

        $profil = $provider->fresh()->providerProfile;
        $this->assertSame(['Bastos', 'Akwa'], $profil->service_areas);
        $this->assertSame(['+237 6XX XXX XXX'], $profil->contact_methods);
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
