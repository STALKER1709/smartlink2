<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ServiceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le contenu de démonstration est fait pour tourner sur la base de production.
 * Ce qui doit tenir n'est donc pas seulement « il crée des lignes », mais
 * qu'il ne touche à rien d'autre et qu'on puisse le rejouer et le retirer.
 */
class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ServiceCategorySeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_it_fills_every_public_surface(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertGreaterThan(10, User::query()->ofRole(User::ROLE_PROVIDER)->count());
        $this->assertGreaterThan(20, Service::count());
        $this->assertGreaterThan(10, ServiceRequest::count());
        $this->assertGreaterThan(0, Review::count());
        $this->assertGreaterThan(0, Conversation::count());
    }

    /**
     * Les descriptions viennent d'un fichier écrit à la main, pas de Faker :
     * une plateforme publique qui affiche du faux latin a l'air en panne.
     */
    public function test_no_service_carries_placeholder_prose(): void
    {
        $this->seed(DemoSeeder::class);

        foreach (Service::all() as $service) {
            $this->assertStringNotContainsStringIgnoringCase('lorem', $service->description);
            $this->assertStringNotContainsStringIgnoringCase('ipsum', $service->description);
            $this->assertGreaterThan(80, mb_strlen($service->description));
        }
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seed(DemoSeeder::class);

        $avant = [User::count(), Service::count(), ServiceRequest::count(), Review::count(), Conversation::count()];

        $this->seed(DemoSeeder::class);

        $this->assertSame($avant, [
            User::count(), Service::count(), ServiceRequest::count(), Review::count(), Conversation::count(),
        ]);
    }

    /**
     * Aucun compte privilégié : `DatabaseSeeder` en crée un dont le mot de
     * passe vient de la fabrique, ce qui est acceptable en développement et
     * jamais en ligne.
     */
    public function test_it_creates_no_administrator(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(0, User::query()->ofRole(User::ROLE_ADMIN)->count());
    }

    public function test_every_account_it_creates_is_marked_as_demo(): void
    {
        $this->seed(DemoSeeder::class);

        foreach (User::all() as $user) {
            $this->assertStringEndsWith(DemoSeeder::DOMAIN, $user->email);
        }
    }

    /**
     * Les états d'abonnement sont répartis exprès : sans cela, l'essai, le
     * palier gratuit et l'abonnement échu n'auraient rien à montrer.
     */
    public function test_it_spreads_providers_over_every_subscription_state(): void
    {
        $this->seed(DemoSeeder::class);

        $etats = User::query()->ofRole(User::ROLE_PROVIDER)->with('subscriptions.plan')->get()
            ->map(fn (User $u) => $u->subscriptions->first()?->status)
            ->unique();

        $this->assertContains('trialing', $etats);
        $this->assertContains('active', $etats);
        $this->assertContains('expired', $etats);

        $paliers = User::query()->ofRole(User::ROLE_PROVIDER)->with('subscriptions.plan')->get()
            ->map(fn (User $u) => $u->subscriptions->first()?->plan?->code)
            ->unique();

        $this->assertContains(Plan::CODE_FREE, $paliers);
        $this->assertContains(Plan::CODE_ESSENTIAL, $paliers);
        $this->assertContains(Plan::CODE_PRO, $paliers);
    }

    /**
     * La moyenne est stockée sur la fiche, pas recalculée à l'affichage : sans
     * le recalcul du seeder, toutes les fiches resteraient à zéro étoile
     * malgré des avis bien présents en base.
     */
    public function test_the_stored_ratings_match_the_seeded_reviews(): void
    {
        $this->seed(DemoSeeder::class);

        $notes = User::query()->ofRole(User::ROLE_PROVIDER)->with('providerProfile')->get()
            ->filter(fn (User $u) => ($u->providerProfile?->rating_count ?? 0) > 0);

        $this->assertGreaterThan(0, $notes->count());

        foreach ($notes as $provider) {
            $this->assertSame(
                $provider->reviewsReceived()->visible()->count(),
                $provider->providerProfile->rating_count,
            );
            $this->assertGreaterThan(0, (float) $provider->providerProfile->rating_avg);
        }
    }

    /**
     * Le palier gratuit ne plafonne pas que les nouvelles publications : un
     * prestataire de démonstration ne doit pas exhiber plus de services que sa
     * formule n'en autorise.
     */
    public function test_no_provider_shows_more_services_than_the_plan_allows(): void
    {
        $this->seed(DemoSeeder::class);

        foreach (User::query()->ofRole(User::ROLE_PROVIDER)->get() as $provider) {
            $plan = $provider->currentPlan();

            if ($plan === null || $plan->allowsUnlimitedServices()) {
                continue;
            }

            $this->assertLessThanOrEqual(
                $plan->max_services,
                $provider->services()->where('status', Service::STATUS_ACTIVE)->count(),
            );
        }
    }

    public function test_the_clear_command_removes_everything_it_created(): void
    {
        $this->seed(DemoSeeder::class);

        $this->artisan('demo:clear', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, User::withTrashed()->count());
        $this->assertSame(0, Service::withTrashed()->count());
        $this->assertSame(0, ServiceRequest::withTrashed()->count());
        $this->assertSame(0, Review::withTrashed()->count());
    }

    /**
     * La sélection porte sur le domaine des adresses. Un vrai compte, même
     * créé le même jour, doit y survivre.
     */
    public function test_the_clear_command_spares_real_accounts(): void
    {
        $this->seed(DemoSeeder::class);
        $vrai = User::factory()->provider()->create(['email' => 'vrai.prestataire@smartlink.cm']);

        $this->artisan('demo:clear', ['--force' => true])->assertSuccessful();

        $this->assertNotNull(User::find($vrai->id));
    }

    public function test_the_clear_command_is_harmless_with_nothing_to_clear(): void
    {
        $this->artisan('demo:clear', ['--force' => true])->assertSuccessful();
    }
}
