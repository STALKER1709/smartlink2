<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La casse de ce que tape le visiteur ne doit jamais décider du résultat.
 *
 * Ces tests existent parce que `where(..., 'like', ...)` est sensible à la
 * casse sur PostgreSQL alors qu'il ne l'est pas sur MySQL ni SQLite : la
 * recherche renvoyait une page vide à qui tapait en minuscules, sans erreur
 * nulle part. Ils tournent donc sur le moteur configuré, quel qu'il soit.
 */
class CaseInsensitiveSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function casings(): array
    {
        return [
            'identique' => ['Plomberie'],
            'minuscules' => ['plomberie'],
            'majuscules' => ['PLOMBERIE'],
            'panachée' => ['PloMBerIe'],
        ];
    }

    #[DataProvider('casings')]
    public function test_a_service_is_found_whatever_the_casing(string $typed): void
    {
        $wanted = Service::factory()->create(['title' => 'Plomberie express Douala']);
        Service::factory()->create(['title' => 'Coiffure à domicile']);

        $this->get(route('services.index', ['term' => $typed]))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->total() === 1
                && $services->first()->is($wanted));
    }

    #[DataProvider('casings')]
    public function test_a_service_is_found_on_its_description_whatever_the_casing(string $typed): void
    {
        $wanted = Service::factory()->create([
            'title' => 'Dépannage express',
            'description' => 'Interventions de plomberie et de robinetterie.',
        ]);
        Service::factory()->create(['title' => 'Coiffure', 'description' => 'Tresses et soins.']);

        $this->get(route('services.index', ['term' => $typed]))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->total() === 1
                && $services->first()->is($wanted));
    }

    #[DataProvider('casings')]
    public function test_a_provider_is_found_whatever_the_casing(string $typed): void
    {
        $wanted = ProviderProfile::factory()->create([
            'business_name' => 'Plomberie Bonamoussadi',
            'is_listed' => true,
        ]);
        ProviderProfile::factory()->create(['business_name' => 'Coiffure Akwa', 'is_listed' => true]);

        $this->get(route('providers.index', ['term' => $typed]))
            ->assertOk()
            ->assertViewHas('providers', fn ($providers) => $providers->total() === 1
                && $providers->first()->is($wanted));
    }

    public function test_the_quarter_filter_ignores_the_casing(): void
    {
        $wanted = Service::factory()->create(['quarter' => 'Bonamoussadi']);
        Service::factory()->create(['quarter' => 'Akwa']);

        $this->get(route('services.index', ['quarter' => 'bonamoussadi']))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->total() === 1
                && $services->first()->is($wanted));
    }

    public function test_the_admin_user_search_ignores_the_casing(): void
    {
        $admin = User::factory()->admin()->create();
        $wanted = User::factory()->client()->create(['name' => 'Aïcha Mballa']);
        User::factory()->client()->create(['name' => 'Jean Dupont']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['term' => 'mballa']))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1
                && $users->first()->is($wanted));
    }
}
