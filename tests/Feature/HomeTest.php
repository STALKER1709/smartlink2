<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
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

    /**
     * Le prix d'appel de la carte prestataire vient du service le moins cher
     * qu'il propose, pas du plus récent ni d'un service masqué. Une carte qui
     * annonce 5 000 FCFA alors que le catalogue commence à 15 000 fait venir
     * un client sur un malentendu.
     */
    public function test_the_provider_card_shows_the_cheapest_visible_price(): void
    {
        $prestataire = User::factory()->provider()->create();
        $profil = ProviderProfile::factory()->create([
            'user_id' => $prestataire->id,
            'is_verified' => true,
        ]);

        Service::factory()->create(['provider_id' => $prestataire->id, 'price_amount' => 15000]);
        Service::factory()->create(['provider_id' => $prestataire->id, 'price_amount' => 8000]);
        Service::factory()->inactive()->create(['provider_id' => $prestataire->id, 'price_amount' => 500]);
        Service::factory()->create(['provider_id' => $prestataire->id, 'price_amount' => null]);

        $this->get(route('home'))->assertViewHas('featuredProviders', function ($profils) use ($profil) {
            $carte = $profils->firstWhere('id', $profil->id);

            return $carte !== null && (int) $carte->min_price === 8000;
        });
    }

    /**
     * Le bouton de la carte dépend de qui regarde. Un visiteur doit voir
     * l'action réelle — la connexion vient ensuite et le ramène ici. Un
     * prestataire, qui ne peut pas envoyer de demande, verrait sinon six
     * boutons menant à un refus.
     */
    public function test_the_card_offers_contact_to_visitors_and_the_profile_to_providers(): void
    {
        $prestataire = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $prestataire->id, 'is_verified' => true]);

        $this->get(route('home'))
            ->assertSee('Contacter')
            ->assertDontSee('Voir la fiche');

        $this->actingAs(User::factory()->provider()->create())
            ->get(route('home'))
            ->assertSee('Voir la fiche')
            ->assertDontSee('Contacter');
    }
}
