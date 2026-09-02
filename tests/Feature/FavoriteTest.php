<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function profil(): ProviderProfile
    {
        return ProviderProfile::factory()->create([
            'user_id' => User::factory()->provider()->create()->id,
            'category_id' => ServiceCategory::factory()->create()->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->post(route('favorites.toggle', $this->profil()))->assertRedirect(route('login'));
    }

    /**
     * Le même bouton ajoute et retire : c'est un seul geste pour l'utilisateur,
     * et cela doit rester un seul comportement.
     */
    public function test_the_heart_toggles(): void
    {
        $client = User::factory()->client()->create();
        $profil = $this->profil();

        $this->actingAs($client)->post(route('favorites.toggle', $profil));
        $this->assertTrue($client->favorites()->whereKey($profil->id)->exists());

        $this->actingAs($client)->post(route('favorites.toggle', $profil));
        $this->assertFalse($client->favorites()->whereKey($profil->id)->exists());
    }

    /**
     * Deux envois rapprochés ne doivent pas créer deux lignes : la contrainte
     * d'unicité de la table le garantit, `toggle()` le respecte.
     */
    public function test_a_provider_is_never_favourited_twice(): void
    {
        $client = User::factory()->client()->create();
        $profil = $this->profil();

        $this->actingAs($client)->post(route('favorites.toggle', $profil));

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_the_list_shows_only_the_users_own_favourites(): void
    {
        $client = User::factory()->client()->create();
        $autre = User::factory()->client()->create();

        $mien = $this->profil();
        $sien = $this->profil();

        $client->favorites()->attach($mien->id);
        $autre->favorites()->attach($sien->id);

        $response = $this->actingAs($client)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee($mien->business_name, false);
        $response->assertDontSee($sien->business_name, false);
    }
}
