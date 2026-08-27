<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_as_client(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '677123456',
            'role' => 'client',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.show', absolute: false));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->clientProfile);
    }

    public function test_new_users_can_register_as_provider(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Provider',
            'email' => 'provider@example.com',
            'phone' => '677654321',
            'role' => 'provider',
            'business_name' => 'Plomberie Test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.show', absolute: false));

        $user = User::where('email', 'provider@example.com')->first();
        $this->assertNotNull($user->providerProfile);
        $this->assertSame('Plomberie Test', $user->providerProfile->business_name);
    }

    public function test_provider_registration_requires_business_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Provider',
            'email' => 'provider2@example.com',
            'phone' => '677000000',
            'role' => 'provider',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('business_name');
        $this->assertGuest();
    }
}
