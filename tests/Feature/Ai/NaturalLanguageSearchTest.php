<?php

namespace Tests\Feature\Ai;

use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Ai\SearchIntent;
use App\Services\Ai\SearchIntentExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NaturalLanguageSearchTest extends TestCase
{
    use RefreshDatabase;

    private ServiceCategory $plumbing;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::factory()->create();
        $this->plumbing = ServiceCategory::factory()->create(['name' => 'Plomberie']);
    }

    public function test_a_free_sentence_becomes_classic_search_filters(): void
    {
        $this->fakeExtraction(new SearchIntent(
            categoryId: $this->plumbing->id,
            categoryName: 'Plomberie',
            city: 'Douala',
            quarter: 'Bonamoussadi',
            keywords: 'fuite évier',
            urgent: true,
        ));

        $this->get(route('services.index', ['q' => "J'ai une fuite sous l'évier à Bonamoussadi"]))
            ->assertRedirect(route('services.index', [
                'category_id' => $this->plumbing->id,
                'city' => 'Douala',
                'quarter' => 'Bonamoussadi',
                'term' => 'fuite évier',
                'available_only' => '1',
            ]))
            ->assertSessionHas('searchIntent');
    }

    public function test_the_results_page_shows_what_was_understood(): void
    {
        $this->fakeExtraction(new SearchIntent(
            categoryId: $this->plumbing->id,
            categoryName: 'Plomberie',
            city: 'Douala',
        ));

        $this->followingRedirects()
            ->get(route('services.index', ['q' => 'un plombier à Douala']))
            ->assertOk()
            ->assertSee(__('ui.search.understood'))
            ->assertSee('Plomberie')
            ->assertSee('Douala');
    }

    public function test_the_filtered_results_actually_match_the_understood_intent(): void
    {
        $wanted = $this->serviceIn($this->plumbing, 'Douala', 'Un plombier de Douala');
        $other = $this->serviceIn(
            ServiceCategory::factory()->create(['name' => 'Coiffure']),
            'Douala',
            'Une coiffeuse de Douala',
        );

        $this->fakeExtraction(new SearchIntent(
            categoryId: $this->plumbing->id,
            categoryName: 'Plomberie',
            city: 'Douala',
        ));

        $this->followingRedirects()
            ->get(route('services.index', ['q' => 'un plombier à Douala']))
            ->assertOk()
            ->assertSee($wanted->title)
            ->assertDontSee($other->title);
    }

    public function test_a_failed_extraction_falls_back_to_a_plain_keyword_search(): void
    {
        $this->fakeExtraction(null);

        $this->get(route('services.index', ['q' => 'quelque chose de flou']))
            ->assertRedirect(route('services.index', ['term' => 'quelque chose de flou']))
            ->assertSessionMissing('searchIntent');
    }

    public function test_an_extraction_that_understood_nothing_also_falls_back(): void
    {
        $this->fakeExtraction(new SearchIntent);

        $this->get(route('services.index', ['q' => 'bonjour']))
            ->assertRedirect(route('services.index', ['term' => 'bonjour']));
    }

    public function test_an_overlong_sentence_is_trimmed_before_anything_else(): void
    {
        $this->fakeExtraction(null);

        $this->get(route('services.index', ['q' => str_repeat('a', 500)]))
            ->assertRedirect(route('services.index', ['term' => str_repeat('a', 300)]));
    }

    public function test_the_classic_search_is_untouched_when_no_sentence_is_given(): void
    {
        $service = $this->serviceIn($this->plumbing, 'Douala', 'Depannage plomberie');

        $this->get(route('services.index', ['category_id' => $this->plumbing->id]))
            ->assertOk()
            ->assertSee($service->title);
    }

    public function test_nothing_reaches_the_api_when_the_ai_is_off(): void
    {
        config()->set('ai.driver', 'rule');

        // L'extracteur réel est en place : s'il tentait un appel, le test
        // échouerait faute de clé. Le garde-fou doit couper avant.
        $extractor = $this->app->make(SearchIntentExtractor::class);

        $this->assertNull($extractor->extract('un plombier à Douala', User::factory()->client()->create()));
    }

    public function test_a_guest_gets_the_keyword_fallback_rather_than_an_error(): void
    {
        config()->set('ai.driver', 'claude');
        config()->set('ai.api_key', 'cle-de-test');
        config()->set('ai.limits.require_authentication', true);

        $extractor = $this->app->make(SearchIntentExtractor::class);

        $this->assertNull($extractor->extract('un plombier à Douala', null));
    }

    public function test_a_category_the_model_invented_is_dropped_rather_than_trusted(): void
    {
        $intent = $this->app->make(SearchIntentExtractor::class)->toIntent([
            'category' => 'Téléportation quantique',
            'city' => 'Douala',
            'quarter' => '',
            'keywords' => 'urgent',
            'urgent' => true,
        ]);

        $this->assertNull($intent->categoryId);
        $this->assertNull($intent->categoryName);
        $this->assertSame('Douala', $intent->city);
        $this->assertTrue($intent->urgent);
    }

    public function test_a_real_category_is_matched_to_its_identifier(): void
    {
        $intent = $this->app->make(SearchIntentExtractor::class)->toIntent([
            'category' => 'Plomberie',
            'city' => '',
            'quarter' => 'Bonamoussadi',
            'keywords' => '',
            'urgent' => false,
        ]);

        $this->assertSame($this->plumbing->id, $intent->categoryId);
        $this->assertSame('Plomberie', $intent->categoryName);
        $this->assertNull($intent->city);
        $this->assertSame('Bonamoussadi', $intent->quarter);
    }

    public function test_oversized_free_text_fields_are_truncated(): void
    {
        $intent = $this->app->make(SearchIntentExtractor::class)->toIntent([
            'category' => '',
            'city' => '',
            'quarter' => str_repeat('z', 500),
            'keywords' => str_repeat('k', 500),
            'urgent' => false,
        ]);

        $this->assertSame(60, mb_strlen($intent->quarter));
        $this->assertSame(80, mb_strlen($intent->keywords));
    }

    private function fakeExtraction(?SearchIntent $intent): void
    {
        $fake = new class($intent) extends SearchIntentExtractor
        {
            public function __construct(private readonly ?SearchIntent $intent)
            {
                // Volontairement sans appel au parent : ce double ne parle à rien.
            }

            public function extract(string $query, ?User $user = null): ?SearchIntent
            {
                return $this->intent;
            }
        };

        $this->app->instance(SearchIntentExtractor::class, $fake);
    }

    private function serviceIn(ServiceCategory $category, string $city, string $title): Service
    {
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id, 'city' => $city]);

        return Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'city' => $city,
            'title' => $title,
            'status' => Service::STATUS_ACTIVE,
            'is_available' => true,
        ]);
    }
}
