<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
use Database\Seeders\ServiceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La table des photographies (`config/imagery.php`).
 *
 * Trois défauts s'y installent sans bruit. Une photo affichée sans le nom de
 * son auteur : presque toutes les licences libres l'exigent, et l'absence ne
 * se voit que le jour où l'auteur la réclame. Un commutateur à faux qu'une vue
 * ignore parce qu'elle a gardé l'URL en dur : chaque visiteur paie une requête
 * vers un hôte que l'exploitant croyait débranché. Et une entrée à moitié
 * remplie, qui affiche l'image et tait la mention.
 */
class RemoteImageryTest extends TestCase
{
    use RefreshDatabase;

    private array $photo = [
        'url' => 'https://exemple.test/plomberie.jpg',
        'auteur' => 'Aïcha Mballa',
        'licence' => 'CC BY 4.0',
        'source' => 'https://exemple.test/page',
    ];

    public function test_the_switch_silences_every_call_to_the_outside(): void
    {
        config()->set('imagery.categories.Plomberie', $this->photo);
        config()->set('imagery.enabled', false);

        $categorie = ServiceCategory::factory()->create(['name' => 'Plomberie']);
        Service::factory()->create(['category_id' => $categorie->id]);

        $this->assertNull(image_categorie('Plomberie'));

        $this->get(route('home'))->assertOk()->assertDontSee('exemple.test');
        $this->get(route('services.index'))->assertOk()->assertDontSee('exemple.test');
    }

    public function test_a_configured_photo_reaches_the_page_with_its_credit(): void
    {
        config()->set('imagery.enabled', true);
        config()->set('imagery.categories.Plomberie', $this->photo);

        $categorie = ServiceCategory::factory()->create(['name' => 'Plomberie']);
        Service::factory()->create(['category_id' => $categorie->id]);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('https://exemple.test/plomberie.jpg', false)
            ->assertSee('Aïcha Mballa — CC BY 4.0', false);
    }

    public function test_a_storage_path_is_resolved_like_any_other_uploaded_file(): void
    {
        // Les deux formes d'URL cohabitent : une photo rapatriée par
        // `photos:import` est un chemin sur le disque de médias, pas une URL.
        config()->set('imagery.enabled', true);
        config()->set('imagery.categories.Coiffure', [
            'url' => 'services/photos/coiffure-1.jpg',
            'auteur' => 'Équipe SmartLink',
            'licence' => 'Propriété SmartLink',
        ]);

        $photo = image_categorie('Coiffure');

        $this->assertNotNull($photo);
        $this->assertStringContainsString('services/photos/coiffure-1.jpg', $photo['url']);
        $this->assertStringStartsWith('http', $photo['url']);
    }

    public function test_an_unknown_category_keeps_its_drawing_rather_than_a_wrong_photo(): void
    {
        config()->set('imagery.enabled', true);

        $this->assertNull(image_categorie('Métier qui n\'existe pas'));
        $this->assertNull(image_categorie(null));
    }

    public function test_a_photo_without_a_credit_is_never_displayed(): void
    {
        // La règle est tenue par le code, pas seulement par ce test : une
        // entrée incomplète peut exister — les propositions à relire en sont —
        // et le commutateur s'actionne d'un mot. Sans ce garde-fou, une photo
        // dont l'auteur n'est pas nommé partirait en ligne sur un simple
        // REMOTE_IMAGES=true.
        config()->set('imagery.enabled', true);

        config()->set('imagery.categories.Plomberie', ['url' => 'https://exemple.test/x.jpg']);
        $this->assertNull(image_categorie('Plomberie'));

        config()->set('imagery.categories.Plomberie', ['url' => 'https://exemple.test/x.jpg', 'auteur' => 'Nom']);
        $this->assertNull(image_categorie('Plomberie'), 'Une licence manquante suffit à retenir la photo.');

        config()->set('imagery.categories.Plomberie', $this->photo);
        $this->assertNotNull(image_categorie('Plomberie'));
    }

    public function test_every_seeded_category_carries_a_photo(): void
    {
        // Une seule catégorie manquante fait une grille bancale : des vignettes
        // photographiques à côté de vignettes dessinées, sur la même rangée. La
        // table des photos et celle des catégories couvrent le même ensemble.
        $this->seed(ServiceCategorySeeder::class);
        config()->set('imagery.enabled', true);

        $sans = ServiceCategory::query()
            ->pluck('name')
            ->reject(fn (string $nom) => image_categorie($nom) !== null)
            ->values()
            ->all();

        $this->assertSame([], $sans, 'Catégories sans photographie : '.implode(', ', $sans));
    }

    public function test_the_check_command_refuses_a_photo_without_a_credit(): void
    {
        config()->set('imagery.categories.Plomberie', ['url' => 'https://exemple.test/x.jpg']);

        $this->artisan('images:check', ['--mentions-seules' => true])
            ->assertExitCode(1);
    }

    public function test_the_legal_notice_says_so_when_no_third_party_photo_is_used(): void
    {
        config()->set('imagery.categories', []);
        config()->set('imagery.cta', null);

        $this->get(route('legal.notice'))
            ->assertOk()
            ->assertSee('Crédits photographiques')
            ->assertSee('Aucune photographie de tiers n\'est employée');
    }

    public function test_the_legal_notice_lists_the_photos_that_are_used(): void
    {
        config()->set('imagery.categories', ['Plomberie' => $this->photo]);

        $this->get(route('legal.notice'))
            ->assertOk()
            ->assertSee('Aïcha Mballa')
            ->assertSee('CC BY 4.0')
            ->assertSee('https://exemple.test/page', false);
    }
}
