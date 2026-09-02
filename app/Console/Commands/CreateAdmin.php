<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Ouvre un compte administrateur.
 *
 * Il n'existe pas d'autre chemin : `/register` n'accepte que les rôles client
 * et prestataire, et c'est très bien ainsi — un formulaire public capable de
 * fabriquer un administrateur serait une porte ouverte.
 *
 * Le mot de passe est demandé à l'écran et jamais accepté en argument. Sur la
 * ligne de commande il resterait dans l'historique du terminal, dans les
 * journaux du shell, et sous les yeux de qui passe derrière : trois endroits
 * où le mot de passe d'un administrateur n'a rien à faire.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {--name= : Nom affiché}
        {--email= : Adresse de connexion}
        {--phone= : Numéro de téléphone, sans le +237}';

    protected $description = 'Ouvre un compte administrateur (mot de passe demandé à l\'écran)';

    public function handle(AuditLogService $auditLog): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Adresse e-mail'))));
        $existant = User::withTrashed()->where('email', $email)->first();

        if ($existant !== null) {
            return $this->promote($existant, $auditLog);
        }

        $name = (string) ($this->option('name') ?: $this->ask('Nom affiché'));
        $phone = (string) ($this->option('phone') ?: $this->ask('Téléphone (sans le +237)'));

        $password = $this->secret('Mot de passe');

        if ($password !== $this->secret('Confirmer le mot de passe')) {
            $this->error('Les deux saisies diffèrent.');

            return self::FAILURE;
        }

        $donnees = compact('name', 'email', 'phone', 'password');

        // Les mêmes règles que l'inscription publique : rien ne justifie qu'un
        // administrateur ait un mot de passe plus faible qu'un client.
        $validateur = Validator::make($donnees, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validateur->fails()) {
            foreach ($validateur->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'locale' => 'fr',
        ]);

        // Hors du `fillable`, donc posé à part : un administrateur ouvert
        // depuis la console n'a personne pour lui envoyer un lien de
        // confirmation. Passé par `create()`, l'attribut serait ignoré en
        // silence et le compte resterait marqué non confirmé.
        $admin->forceFill(['email_verified_at' => now()])->save();

        $auditLog->log($admin, 'admin.created', $admin);

        $this->info("Compte administrateur créé : {$admin->email}");
        $this->line('Connexion sur /login, puis /admin.');

        return self::SUCCESS;
    }

    /**
     * Promeut un compte existant plutôt que d'échouer sur « adresse déjà
     * prise » : c'est presque toujours ce qu'on voulait faire, et le refus sec
     * pousse à créer une seconde adresse pour la même personne.
     */
    private function promote(User $user, AuditLogService $auditLog): int
    {
        if ($user->trashed()) {
            $this->error('Ce compte a été supprimé. Le restaurer d\'abord, si c\'est bien voulu.');

            return self::FAILURE;
        }

        if ($user->role === User::ROLE_ADMIN) {
            $this->info("{$user->email} est déjà administrateur.");

            return self::SUCCESS;
        }

        $this->warn("{$user->email} existe déjà, avec le rôle « {$user->role} ».");

        if (! $this->confirm('Le passer administrateur ?', false)) {
            $this->line('Rien n\'a été changé.');

            return self::SUCCESS;
        }

        $ancien = $user->role;
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        $auditLog->log($user, 'admin.promoted', $user, ['ancien_role' => $ancien]);

        $this->info("{$user->email} est désormais administrateur.");
        $this->comment('Son mot de passe est inchangé.');

        return self::SUCCESS;
    }
}
