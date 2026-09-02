<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque page s'affiche, pour le rôle à qui elle est destinée.
 *
 * Une vue peut être syntaxiquement fausse sans que rien ne le dise : Blade ne
 * compile qu'à la première demande, et un test qui n'ouvre jamais la page ne
 * la compile jamais. L'administration des paliers a répondu 500 pendant toute
 * une refonte — une interpolation restée à l'intérieur d'une liaison de
 * composant — sans qu'aucun test ne la touche.
 *
 * Ce balayage ne juge pas le contenu : il ouvre, et refuse les 500. C'est peu,
 * et c'est exactement ce qui manquait.
 */
class PageRenduTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $prestataire;

    private User $admin;

    private Service $service;

    private ProviderProfile $profil;

    private ServiceCategory $categorie;

    private Plan $plan;

    private ServiceRequest $demande;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categorie = ServiceCategory::factory()->create();
        $this->plan = Plan::factory()->create();

        $this->client = User::factory()->client()->create();
        $this->prestataire = User::factory()->provider()->create();
        $this->admin = User::factory()->admin()->create();

        $this->profil = ProviderProfile::factory()->create([
            'user_id' => $this->prestataire->id,
            'category_id' => $this->categorie->id,
            'is_listed' => true,
        ]);

        $this->service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
            'category_id' => $this->categorie->id,
            'status' => Service::STATUS_ACTIVE,
        ]);

        $this->demande = ServiceRequest::factory()->create([
            'client_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_id' => $this->service->id,
            'status' => ServiceRequest::STATUS_ACCEPTED,
        ]);

        $this->conversation = Conversation::factory()->create([
            'client_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'request_id' => $this->demande->id,
        ]);
    }

    /**
     * @param  array<int, string>  $urls
     */
    private function ouvrir(?User $utilisateur, array $urls): void
    {
        $casses = [];

        foreach ($urls as $url) {
            if ($utilisateur !== null) {
                $this->actingAs($utilisateur);
            }

            $statut = $this->get($url)->getStatusCode();

            if ($statut >= 500) {
                $casses[] = $url.' → '.$statut;
            }
        }

        $this->assertSame([], $casses, 'Pages en erreur : '.implode(', ', $casses));
    }

    public function test_the_public_pages_render(): void
    {
        $this->ouvrir(null, [
            '/',
            '/services',
            '/services?term=plomb&city=Douala&available_only=1',
            '/services?category_id='.$this->categorie->id,
            '/services/'.$this->service->slug,
            '/prestataires',
            '/prestataires?verified_only=1&term=jean',
            '/prestataires/'.$this->profil->id,
            '/aide',
            '/conditions-generales',
            '/mentions-legales',
            '/confidentialite',
            '/hors-ligne',
            '/login',
            '/register',
            '/forgot-password',
        ]);
    }

    public function test_the_client_pages_render(): void
    {
        $this->ouvrir($this->client, [
            '/dashboard',
            '/bienvenue/1',
            '/bienvenue/2',
            '/profile',
            '/client/profile',
            '/requests',
            '/requests?status=sent',
            '/requests/create',
            '/requests/'.$this->demande->id,
            '/conversations',
            '/conversations?q=jean',
            '/conversations/'.$this->conversation->id,
            '/notifications',
            '/favoris',
            '/litiges',
            '/demandes/'.$this->demande->id.'/litige',
        ]);
    }

    public function test_the_provider_pages_render(): void
    {
        $this->ouvrir($this->prestataire, [
            '/dashboard',
            '/profile',
            '/provider/profile',
            '/provider/services',
            '/provider/services?statut=actifs',
            '/provider/services?statut=pause',
            '/provider/services/create',
            '/provider/services/'.$this->service->id.'/edit',
            '/provider/statistics',
            '/provider/reviews',
            '/provider/transactions',
            '/provider/subscription',
            '/provider/subscription/'.$this->plan->id,
            '/requests',
            '/conversations',
        ]);
    }

    public function test_the_back_office_pages_render(): void
    {
        $this->ouvrir($this->admin, [
            '/admin',
            '/admin/users',
            '/admin/users?role=provider&q=jean',
            '/admin/services',
            '/admin/services?q=plomberie',
            '/admin/categories',
            '/admin/categories/create',
            '/admin/categories/'.$this->categorie->id.'/edit',
            '/admin/plans',
            // Celle-ci répondait 500 : une interpolation laissée à l'intérieur
            // d'une liaison de composant, sur un écran que rien n'ouvrait.
            '/admin/plans/'.$this->plan->id.'/edit',
            '/admin/moderation',
            '/admin/litiges',
            '/admin/verifications',
        ]);
    }
}
