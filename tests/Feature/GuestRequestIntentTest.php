<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un visiteur qui découvre un service et veut le demander ne doit pas perdre
 * ce service en chemin. C'est le seul entonnoir d'acquisition de la
 * plateforme : l'y faire repartir de zéro après connexion, c'est le perdre.
 */
class GuestRequestIntentTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $provider = User::factory()->provider()->create();
        $categorie = ServiceCategory::factory()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id, 'category_id' => $categorie->id]);

        $this->service = Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => $categorie->id,
            'status' => Service::STATUS_ACTIVE,
            'is_available' => true,
        ]);
    }

    /**
     * Le bouton porte l'action réelle et pointe vers elle, pas vers /login :
     * c'est ce qui permet au middleware de mémoriser l'intention.
     */
    public function test_the_guest_call_to_action_points_at_the_request(): void
    {
        $this->get(route('services.show', $this->service))
            ->assertOk()
            ->assertSee(route('requests.create', ['service_id' => $this->service->id]), false);
    }

    public function test_a_guest_is_sent_to_login_and_brought_back_after_signing_in(): void
    {
        $cible = route('requests.create', ['service_id' => $this->service->id]);

        $this->get($cible)->assertRedirect(route('login'));

        $client = User::factory()->client()->create();

        $this->post(route('login'), [
            'login' => $client->email,
            'password' => 'password',
        ])->assertRedirect($cible);
    }

    /**
     * Et une fois revenu, le formulaire porte bien le service visé — sans quoi
     * le détour n'aurait servi à rien.
     */
    public function test_the_form_arrives_carrying_the_service(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('requests.create', ['service_id' => $this->service->id]))
            ->assertOk()
            ->assertSee($this->service->title);
    }
}
