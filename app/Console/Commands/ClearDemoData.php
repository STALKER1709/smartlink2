<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Retire le contenu de démonstration, et lui seul.
 *
 * La sélection porte sur le domaine des adresses, jamais sur une date ou un
 * identifiant : c'est la seule marque qui distingue à coup sûr un compte de
 * démonstration d'un vrai. Les services, demandes, conversations et avis
 * partent avec leur propriétaire, par cascade en base.
 */
class ClearDemoData extends Command
{
    protected $signature = 'demo:clear {--force : Ne pas demander confirmation}';

    protected $description = 'Supprime définitivement les comptes de démonstration et tout ce qui en dépend';

    public function handle(): int
    {
        $comptes = User::withTrashed()
            ->where('email', 'like', '%'.DemoSeeder::DOMAIN)
            ->get();

        if ($comptes->isEmpty()) {
            $this->info('Aucune donnée de démonstration.');

            return self::SUCCESS;
        }

        $this->line($comptes->count().' comptes de démonstration trouvés.');

        if (! $this->option('force') && ! $this->confirm('Les supprimer définitivement ?', false)) {
            $this->comment('Rien n\'a été supprimé.');

            return self::SUCCESS;
        }

        // Suppression définitive : les `deleteOnCascade` des migrations
        // emportent services, demandes, conversations, avis et abonnements.
        // Un `delete()` d'Eloquent ne ferait qu'une suppression douce, et le
        // contenu resterait affiché.
        DB::table('users')->whereIn('id', $comptes->pluck('id'))->delete();

        $this->info($comptes->count().' comptes supprimés, avec tout ce qui en dépendait.');

        return self::SUCCESS;
    }
}
