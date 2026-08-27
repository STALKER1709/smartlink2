<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceCategory;
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

    public function test_every_declared_photo_carries_its_author_and_licence(): void
    {
        $entrees = collect(config('imagery.categories', []))
            ->put('cta', config('imagery.cta'))
            ->filter(fn ($entree) => is_array($entree) && ! empty($entree['url']));

        $incompletes = $entrees
            ->filter(fn ($entree) => empty($entree['auteur']) || empty($entree['licence']))
            ->keys()
            ->all();

        $this->assertSame([], $incompletes,
            'Photographies sans mention d\'auteur ou de licence : '.implode(', ', $incompletes));
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
            ->assertSee('Aucune photographie de tiers n\'est employée', false);
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
