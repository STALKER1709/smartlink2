<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Une page de liste doit coûter le même nombre de requêtes qu'elle porte trois
 * lignes ou douze.
 *
 * Une relation lue depuis une vue sans avoir été préchargée ajoute une requête
 * par ligne. Rien ne casse, aucun test ne rougit, et la page reste rapide sur
 * SQLite — où la base est un fichier local. En production elle est chez
 * Supabase : chaque ligne devient un aller-retour réseau, et la liste des
 * demandes en payait dix par affichage.
 *
 * On n'inscrit pas un nombre attendu : il changerait à chaque ajout légitime.
 * On vérifie l'invariant, c'est-à-dire que le nombre ne dépend pas du volume.
 */
class RequeteParLigneTest extends TestCase
{
    use RefreshDatabase;

    private function peupler(User $client, int $combien): void
    {
        $categorie = ServiceCategory::factory()->create();

        for ($i = 0; $i < $combien; $i++) {
            $prestataire = User::factory()->provider()->create();
            ProviderProfile::factory()->create([
                'user_id' => $prestataire->id,
                'category_id' => $categorie->id,
                'is_listed' => true,
            ]);

            $service = Service::factory()->create([
                'provider_id' => $prestataire->id,
                'category_id' => $categorie->id,
                'status' => Service::STATUS_ACTIVE,
            ]);

            $demande = ServiceRequest::factory()->create([
                'client_id' => $client->id,
                'provider_id' => $prestataire->id,
                'service_id' => $service->id,
                'status' => ServiceRequest::STATUS_ACCEPTED,
            ]);

            Conversation::factory()->create([
                'client_id' => $client->id,
                'provider_id' => $prestataire->id,
                'request_id' => $demande->id,
            ]);
        }
    }

    private function requetesPour(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($url)->assertOk();

        $nombre = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $nombre;
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function pages(): array
    {
        return [
            'mes demandes' => ['/requests'],
            'mes messages' => ['/conversations'],
            'tableau de bord' => ['/dashboard'],
        ];
    }

    #[DataProvider('pages')]
    public function test_a_list_page_costs_the_same_whatever_it_holds(string $url): void
    {
        $client = User::factory()->client()->create();
        $this->actingAs($client);

        $this->peupler($client, 3);
        $petit = $this->requetesPour($url);

        $this->peupler($client, 9);
        $grand = $this->requetesPour($url);

        $this->assertLessThanOrEqual($petit, $grand, sprintf(
            '%s passe de %d à %d requêtes en passant de 3 à 12 lignes : une relation est lue sans avoir été préchargée.',
            $url, $petit, $grand,
        ));
    }

    /**
     * La conversation ouverte porte la colonne des vingt derniers fils : son
     * coût ne doit pas dépendre de leur nombre non plus.
     */
    public function test_an_open_conversation_costs_the_same_whatever_the_sidebar_holds(): void
    {
        $client = User::factory()->client()->create();
        $this->actingAs($client);

        $this->peupler($client, 3);
        $url = '/conversations/'.Conversation::query()->firstOrFail()->id;
        $petit = $this->requetesPour($url);

        $this->peupler($client, 9);
        $grand = $this->requetesPour($url);

        $this->assertLessThanOrEqual($petit, $grand, sprintf(
            'La conversation passe de %d à %d requêtes quand la colonne des fils se remplit.',
            $petit, $grand,
        ));
    }
}
