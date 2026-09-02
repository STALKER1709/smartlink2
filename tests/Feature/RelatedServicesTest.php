<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Services similaires » n'a d'intérêt que s'il permet de comparer. Rempli par
 * les autres annonces du même prestataire, il ne propose aucun choix.
 */
class RelatedServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_providers_come_before_the_authors_own_services(): void
    {
        $categorie = ServiceCategory::factory()->create();
        $vedette = $this->service($categorie, 'Le service consulté');
        $sien = $this->service($categorie, 'Autre annonce du même', $vedette->provider);
        $concurrent = $this->service($categorie, 'Annonce d\'un concurrent');

        $reponse = $this->get(route('services.show', $vedette))->assertOk();
        $similaires = $reponse->viewData('relatedServices');

        $this->assertSame(
            [$concurrent->id, $sien->id],
            $similaires->pluck('id')->all(),
            'Le concurrent doit passer devant l\'autre annonce du même prestataire.',
        );
    }

    /**
     * Quand personne d'autre n'exerce le métier, ses propres annonces valent
     * mieux qu'une section vide.
     */
    public function test_the_authors_own_services_still_appear_when_alone(): void
    {
        $categorie = ServiceCategory::factory()->create();
        $vedette = $this->service($categorie, 'Le service consulté');
        $sien = $this->service($categorie, 'Autre annonce du même', $vedette->provider);

        $similaires = $this->get(route('services.show', $vedette))->viewData('relatedServices');

        $this->assertSame([$sien->id], $similaires->pluck('id')->all());
    }

    public function test_the_consulted_service_never_appears_among_its_own_suggestions(): void
    {
        $categorie = ServiceCategory::factory()->create();
        $vedette = $this->service($categorie, 'Le service consulté');
        $this->service($categorie, 'Un autre');

        $similaires = $this->get(route('services.show', $vedette))->viewData('relatedServices');

        $this->assertNotContains($vedette->id, $similaires->pluck('id')->all());
    }

    private function service(ServiceCategory $categorie, string $titre, ?User $provider = null): Service
    {
        if ($provider === null) {
            $provider = User::factory()->provider()->create();
            ProviderProfile::factory()->create(['user_id' => $provider->id, 'category_id' => $categorie->id]);
        }

        return Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => $categorie->id,
            'title' => $titre,
            'status' => Service::STATUS_ACTIVE,
            'is_available' => true,
        ]);
    }
}
