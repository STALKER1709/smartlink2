<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    /**
     * La demande de réinitialisation envoie un courriel à une adresse que le
     * demandeur ne possède pas forcément : sans plafond, la route sert à
     * bombarder un tiers aux frais de notre réputation d'expéditeur.
     */
    public function test_reset_link_requests_are_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post('/forgot-password', ['email' => $user->email])
                ->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertStatus(429);
    }

    /**
     * Le formulaire de nouveau mot de passe consomme un jeton : le plafond
     * ferme le passage en force sur un jeton deviné.
     */
    public function test_password_reset_submissions_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->post('/reset-password', [
                'token' => 'jeton-invalide',
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])->assertStatus(302);
        }

        $this->post('/reset-password', [
            'token' => 'jeton-invalide',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(429);
    }
}
