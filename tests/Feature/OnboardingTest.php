<?php

namespace Tests\Feature;

use App\Http\Controllers\OnboardingController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('onboarding.show'))->assertRedirect(route('login'));
    }

    public function test_registration_lands_on_the_welcome_screens(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Nouvelle Cliente',
            'email' => 'nouvelle@exemple.cm',
            'phone' => '698765432',
            'role' => User::ROLE_CLIENT,
            'password' => 'Ndjoya-Ferme-88',
            'password_confirmation' => 'Ndjoya-Ferme-88',
        ]);

        $response->assertRedirect(route('onboarding.show', absolute: false));
        $this->assertNull(User::where('email', 'nouvelle@exemple.cm')->firstOrFail()->onboarded_at);
    }

    /**
     * Terminer et passer mènent au même endroit : l'accueil se voit une fois,
     * et le forcer à qui l'a écarté serait le punir de son choix.
     */
    public function test_finishing_marks_the_account_and_returns_to_the_dashboard(): void
    {
        $client = User::factory()->client()->create(['onboarded_at' => null]);

        $this->actingAs($client)
            ->post(route('onboarding.finish'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($client->fresh()->onboarded_at);
    }

    /**
     * Une URL tapée à la main ne doit pas donner une page d'erreur.
     */
    public function test_an_out_of_range_step_returns_to_the_first(): void
    {
        $client = User::factory()->client()->create(['onboarded_at' => null]);

        $this->actingAs($client)->get(route('onboarding.show', 9))->assertRedirect(route('onboarding.show', 1));
        $this->actingAs($client)->get(route('onboarding.show', 1))->assertOk();
    }

    public function test_every_step_renders(): void
    {
        $client = User::factory()->client()->create(['onboarded_at' => null]);

        foreach (OnboardingController::etapes() as $index => $etape) {
            $this->actingAs($client)
                ->get(route('onboarding.show', $index + 1))
                ->assertOk()
                ->assertSee($etape['titre'], false);
        }
    }
}
