<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * `admin:create` est le seul chemin vers un compte administrateur : le
 * formulaire public n'accepte que client et prestataire. Ce qui doit tenir,
 * c'est qu'il ne soit ni contournable ni plus laxiste que l'inscription.
 */
class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_opens_an_administrator_account(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'Torrent-Falaise-77')
            ->expectsQuestion('Confirmer le mot de passe', 'Torrent-Falaise-77')
            ->assertSuccessful();

        $admin = User::where('email', 'patrick@smartlink.cm')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('Torrent-Falaise-77', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    /**
     * Sans adresse confirmée, le compte serait inutilisable : personne n'est là
     * pour envoyer le lien de confirmation à un administrateur ouvert depuis la
     * console.
     */
    public function test_the_new_administrator_can_sign_in(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'Torrent-Falaise-77')
            ->expectsQuestion('Confirmer le mot de passe', 'Torrent-Falaise-77')
            ->assertSuccessful();

        $this->post(route('login'), [
            'login' => 'patrick@smartlink.cm',
            'password' => 'Torrent-Falaise-77',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_two_different_passwords_create_nothing(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'Torrent-Falaise-77')
            ->expectsQuestion('Confirmer le mot de passe', 'Torrent-Falaise-78')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    /**
     * Rien ne justifie qu'un administrateur ait un mot de passe plus faible
     * qu'un client : ce sont les règles de l'inscription publique qui valent.
     */
    public function test_a_weak_password_is_refused(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'abc')
            ->expectsQuestion('Confirmer le mot de passe', 'abc')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_a_taken_phone_number_is_refused(): void
    {
        User::factory()->client()->create(['phone' => '699887766']);

        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'Torrent-Falaise-77')
            ->expectsQuestion('Confirmer le mot de passe', 'Torrent-Falaise-77')
            ->assertFailed();

        $this->assertSame(0, User::query()->ofRole(User::ROLE_ADMIN)->count());
    }

    /**
     * Échouer sur « adresse déjà prise » pousserait à ouvrir une seconde
     * adresse pour la même personne. La promotion est presque toujours ce qu'on
     * voulait faire — mais elle se demande.
     */
    public function test_an_existing_account_is_promoted_after_confirmation(): void
    {
        $client = User::factory()->client()->create(['email' => 'patrick@smartlink.cm']);
        $motDePasse = $client->password;

        $this->artisan('admin:create', ['--email' => 'patrick@smartlink.cm'])
            ->expectsConfirmation('Le passer administrateur ?', 'yes')
            ->assertSuccessful();

        $client->refresh();
        $this->assertSame(User::ROLE_ADMIN, $client->role);
        $this->assertSame($motDePasse, $client->password, 'Le mot de passe ne doit pas être touché.');
    }

    public function test_a_refused_promotion_changes_nothing(): void
    {
        $client = User::factory()->client()->create(['email' => 'patrick@smartlink.cm']);

        $this->artisan('admin:create', ['--email' => 'patrick@smartlink.cm'])
            ->expectsConfirmation('Le passer administrateur ?', 'no')
            ->assertSuccessful();

        $this->assertSame(User::ROLE_CLIENT, $client->fresh()->role);
    }

    public function test_promoting_an_administrator_is_a_no_op(): void
    {
        User::factory()->admin()->create(['email' => 'patrick@smartlink.cm']);

        $this->artisan('admin:create', ['--email' => 'patrick@smartlink.cm'])->assertSuccessful();

        $this->assertSame(1, User::query()->ofRole(User::ROLE_ADMIN)->count());
    }

    /**
     * Un compte supprimé qui reparaît en administrateur serait une surprise
     * désagréable : la restauration est une décision à part.
     */
    public function test_a_deleted_account_is_not_silently_revived(): void
    {
        $client = User::factory()->client()->create(['email' => 'patrick@smartlink.cm']);
        $client->delete();

        $this->artisan('admin:create', ['--email' => 'patrick@smartlink.cm'])->assertFailed();

        $this->assertSame(0, User::query()->ofRole(User::ROLE_ADMIN)->count());
    }

    public function test_the_creation_is_traced(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Patrick Mvogo',
            '--email' => 'patrick@smartlink.cm',
            '--phone' => '699887766',
        ])
            ->expectsQuestion('Mot de passe', 'Torrent-Falaise-77')
            ->expectsQuestion('Confirmer le mot de passe', 'Torrent-Falaise-77')
            ->assertSuccessful();

        $this->assertSame(1, AuditLog::where('action', 'admin.created')->count());
    }

    /**
     * L'inscription publique ne doit jamais pouvoir fabriquer un
     * administrateur : c'est ce qui rend cette commande nécessaire.
     */
    public function test_the_public_form_cannot_create_an_administrator(): void
    {
        $this->post(route('register'), [
            'name' => 'Intrus',
            'email' => 'intrus@example.cm',
            'phone' => '699000111',
            'role' => User::ROLE_ADMIN,
            'password' => 'Torrent-Falaise-77',
            'password_confirmation' => 'Torrent-Falaise-77',
        ])->assertSessionHasErrors('role');

        $this->assertSame(0, User::query()->ofRole(User::ROLE_ADMIN)->count());
    }
}
