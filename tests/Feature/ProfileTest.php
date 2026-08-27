<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'phone' => $user->phone,
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'phone' => $user->phone,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_phone_number_can_be_updated(): void
    {
        $user = User::factory()->create(['phone' => '+237600000001']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => '+237600000002',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('+237600000002', $user->refresh()->phone);
    }

    public function test_changing_the_phone_number_drops_its_verification(): void
    {
        // La coche de confiance porte sur un numéro précis. Sans cette remise
        // à zéro elle survivait au changement, et désignait un téléphone que
        // personne n'a jamais confirmé.
        $user = User::factory()->create([
            'phone' => '+237600000001',
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => '+237600000002',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->phone_verified_at);
    }

    public function test_the_phone_number_of_another_account_is_refused(): void
    {
        // La colonne est unique en base : sans la règle de validation, le
        // formulaire sortait en erreur de base de données plutôt qu'en message
        // sous le champ.
        $autre = User::factory()->create(['phone' => '+237600000009']);
        $user = User::factory()->create(['phone' => '+237600000001']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'phone' => $autre->phone,
                'email' => $user->email,
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame('+237600000001', $user->refresh()->phone);
    }

    public function test_keeping_its_own_phone_number_is_allowed(): void
    {
        $user = User::factory()->create(['phone' => '+237600000001']);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nom modifié',
                'phone' => $user->phone,
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nom modifié', $user->refresh()->name);
    }
}
