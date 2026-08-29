<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La pièce d'identité d'un prestataire est une donnée d'identité : elle ne doit
 * jamais être joignable autrement que par une route qui vérifie qui demande.
 *
 * Ces tests montent la garde sur les deux moitiés de cette garantie — le
 * fichier n'atterrit pas sur un disque public, et la route ne le rend qu'à
 * l'administrateur ou au prestataire qui l'a déposé.
 */
class IdDocumentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function profilAvecPiece(): ProviderProfile
    {
        Storage::fake(id_documents_disk());

        $provider = User::factory()->provider()->create();
        $profil = ProviderProfile::factory()->create(['user_id' => $provider->id]);

        Storage::disk(id_documents_disk())->put('piece.jpg', 'CONTENU CONFIDENTIEL');
        $profil->update(['id_card_path' => 'piece.jpg']);

        return $profil->refresh();
    }

    /**
     * Le cœur du correctif : le dépôt ne touche pas le disque public.
     */
    public function test_uploaded_id_card_never_lands_on_the_public_disk(): void
    {
        Storage::fake(media_disk());
        Storage::fake(id_documents_disk());

        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $provider->refresh();

        $this->actingAs($provider)->put(route('provider.profile.update'), [
            'business_name' => 'Plomberie Test',
            'city' => 'Douala',
            'id_card' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect();

        $chemin = $provider->providerProfile->refresh()->id_card_path;

        $this->assertNotNull($chemin, 'La pièce aurait dû être enregistrée.');
        Storage::disk(id_documents_disk())->assertExists($chemin);
        Storage::disk(media_disk())->assertMissing($chemin);
    }

    public function test_guest_cannot_read_an_id_document(): void
    {
        $profil = $this->profilAvecPiece();

        $this->get(route('provider-profiles.id-document', $profil))
            ->assertRedirect(route('login'));
    }

    public function test_a_client_cannot_read_someone_elses_id_document(): void
    {
        $profil = $this->profilAvecPiece();
        $client = User::factory()->client()->create();

        $this->actingAs($client)
            ->get(route('provider-profiles.id-document', $profil))
            ->assertForbidden();
    }

    /**
     * Le cas qu'on oublie : un prestataire est authentifié et a le bon rôle,
     * mais le document est celui d'un confrère.
     */
    public function test_another_provider_cannot_read_the_document(): void
    {
        $profil = $this->profilAvecPiece();
        $autre = User::factory()->provider()->create();

        $this->actingAs($autre)
            ->get(route('provider-profiles.id-document', $profil))
            ->assertForbidden();
    }

    public function test_the_owner_can_read_their_own_document(): void
    {
        $profil = $this->profilAvecPiece();

        $response = $this->actingAs($profil->user)
            ->get(route('provider-profiles.id-document', $profil));

        $response->assertOk();
        $this->assertSame('CONTENU CONFIDENTIEL', $response->streamedContent());
    }

    public function test_an_admin_can_read_the_document(): void
    {
        $profil = $this->profilAvecPiece();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('provider-profiles.id-document', $profil));

        $response->assertOk();

        // Aucun intermédiaire ne doit garder copie d'une pièce d'identité.
        $this->assertStringContainsString('no-store', $response->headers->get('cache-control'));
        $this->assertStringContainsString('private', $response->headers->get('cache-control'));
    }

    public function test_a_missing_file_answers_404_rather_than_leaking_an_error(): void
    {
        Storage::fake(id_documents_disk());

        $provider = User::factory()->provider()->create();
        $profil = ProviderProfile::factory()->create(['user_id' => $provider->id]);
        $profil->update(['id_card_path' => 'jamais-ecrit.jpg']);

        $this->actingAs($provider)
            ->get(route('provider-profiles.id-document', $profil))
            ->assertNotFound();
    }

    /**
     * Un document rejeté est effacé du disque, pas seulement détaché : sinon la
     * pièce reste indéfiniment sur le serveur sans que rien ne la référence.
     */
    public function test_rejecting_a_document_deletes_the_file(): void
    {
        $profil = $this->profilAvecPiece();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $profil))
            ->assertRedirect();

        Storage::disk(id_documents_disk())->assertMissing('piece.jpg');
        $this->assertNull($profil->refresh()->id_card_path);
    }
}
