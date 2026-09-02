<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Supprimer un compte est une suppression douce : la ligne reste, donc la
 * cascade des clés étrangères ne part jamais et tout ce qui en dépend survit.
 * C'est voulu — les demandes et les conversations restent lisibles par l'autre
 * partie, les avis déjà laissés continuent de compter.
 *
 * Mais « rester en base » ne veut pas dire « rester visible ni rester stocké ».
 * Ces tests couvrent les deux moitiés : ce qui doit disparaître de la vitrine,
 * et ce qui doit disparaître du disque.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function prestataireAvecService(): array
    {
        $provider = User::factory()->provider()->create();
        $profile = ProviderProfile::factory()->create([
            'user_id' => $provider->id,
            'is_listed' => true,
        ]);

        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => ServiceCategory::factory()->create()->id,
            'status' => Service::STATUS_ACTIVE,
        ]);

        return [$provider->refresh(), $profile, $service];
    }

    private function supprimerLeCompte(User $user): void
    {
        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');
    }

    /**
     * Le défaut d'origine : la fiche répondait 500, pas 404.
     *
     * La garde ne contrôlait que `is_listed`, resté vrai sur le profil d'un
     * compte parti. Elle laissait passer, puis la vue déréférençait un
     * prestataire que la relation rend `null`. Sur une URL publique, indexable
     * et faite pour être partagée.
     */
    public function test_a_departed_providers_service_page_answers_404_not_500(): void
    {
        [$provider, , $service] = $this->prestataireAvecService();

        $this->supprimerLeCompte($provider);

        $this->get(route('services.show', $service))->assertNotFound();
    }

    /**
     * Le même 404, mais sans le ménage — c'est ce test qui éprouve la garde.
     *
     * Les comptes supprimés avant l'arrivée de `AccountDeletionService` gardent
     * un profil à `is_listed = true`. Le ménage ne repassera jamais sur eux :
     * seule la garde des contrôleurs les protège, et elle doit tenir seule.
     */
    public function test_the_guard_alone_stops_a_soft_deleted_account_left_listed(): void
    {
        [$provider, $profile, $service] = $this->prestataireAvecService();

        // Suppression « à l'ancienne » : la ligne part, l'état reste intact.
        $provider->delete();

        $this->assertTrue((bool) $profile->refresh()->is_listed, 'Le profil doit rester listé pour que le test ait un sens.');

        $this->get(route('services.show', $service))->assertNotFound();
        $this->get(route('providers.show', $profile))->assertNotFound();
    }

    public function test_a_departed_providers_profile_page_answers_404(): void
    {
        [$provider, $profile] = $this->prestataireAvecService();

        $this->supprimerLeCompte($provider);

        $this->get(route('providers.show', $profile))->assertNotFound();
    }

    /**
     * Le contrôle d'existence passe avant le laissez-passer administrateur :
     * sinon un administrateur restait le seul à récolter le 500.
     */
    public function test_even_an_admin_gets_404_on_a_departed_providers_page(): void
    {
        [$provider, , $service] = $this->prestataireAvecService();
        $admin = User::factory()->admin()->create();

        $this->supprimerLeCompte($provider);

        $this->actingAs($admin)->get(route('services.show', $service))->assertNotFound();
    }

    public function test_the_services_leave_the_public_listing(): void
    {
        [$provider, , $service] = $this->prestataireAvecService();

        $this->get(route('services.index'))->assertSee($service->title);

        $this->supprimerLeCompte($provider);

        $this->get(route('services.index'))->assertDontSee($service->title);
    }

    /**
     * L'état lui-même est corrigé, pas seulement masqué par la garde : rien
     * ailleurs ne doit se fier à un `is_listed` resté vrai.
     */
    public function test_the_profile_is_unlisted_and_the_services_deactivated(): void
    {
        [$provider, $profile, $service] = $this->prestataireAvecService();

        $this->supprimerLeCompte($provider);

        $this->assertFalse((bool) $profile->refresh()->is_listed);
        $this->assertSame(Service::STATUS_INACTIVE, $service->refresh()->status);
    }

    /**
     * Une pièce d'identité n'a plus aucune raison d'exister une fois le compte
     * parti : la vérification qu'elle servait n'a plus d'objet.
     */
    public function test_the_id_document_is_erased_from_disk(): void
    {
        Storage::fake(id_documents_disk());

        [$provider, $profile] = $this->prestataireAvecService();

        Storage::disk(id_documents_disk())->put('piece.jpg', 'CONTENU');
        $profile->update(['id_card_path' => 'piece.jpg', 'id_card_verified' => true]);

        $this->supprimerLeCompte($provider);

        Storage::disk(id_documents_disk())->assertMissing('piece.jpg');
        $this->assertNull($profile->refresh()->id_card_path);
    }

    /**
     * Ce qui doit survivre, à l'inverse : la trace des échanges passés.
     */
    public function test_a_client_account_can_be_deleted_too(): void
    {
        $client = User::factory()->client()->create();

        $this->supprimerLeCompte($client);

        $this->assertSoftDeleted('users', ['id' => $client->id]);
    }
}
