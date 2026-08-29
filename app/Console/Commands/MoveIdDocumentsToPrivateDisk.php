<?php

namespace App\Console\Commands;

use App\Models\ProviderProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Déplace les pièces d'identité déjà déposées du disque public vers le disque
 * privé.
 *
 * Ces documents ont d'abord été écrits dans `storage/app/public/id_cards`, donc
 * servis par le serveur web sans passer par Laravel : joignables par leur URL,
 * sans authentification. Le code n'écrit plus jamais là, mais les fichiers déjà
 * en place y restent tant que cette commande n'a pas tourné.
 *
 * À lancer une fois après le déploiement, sur chaque environnement qui a reçu
 * des dépôts.
 */
class MoveIdDocumentsToPrivateDisk extends Command
{
    protected $signature = 'id-documents:secure
                            {--dry-run : Montre ce qui serait déplacé sans rien écrire}';

    protected $description = 'Déplace les pièces d\'identité du disque public vers le disque privé';

    public function handle(): int
    {
        $sec = $this->option('dry-run');
        $source = Storage::disk(media_disk());
        $cible = Storage::disk(id_documents_disk());

        $profils = ProviderProfile::query()
            ->whereNotNull('id_card_path')
            ->get();

        if ($profils->isEmpty()) {
            $this->info('Aucune pièce d\'identité enregistrée.');

            return self::SUCCESS;
        }

        $deplaces = 0;
        $absents = 0;
        $dejaEnPlace = 0;

        foreach ($profils as $profil) {
            $chemin = $profil->id_card_path;

            if ($cible->exists($chemin)) {
                $dejaEnPlace++;

                continue;
            }

            if (! $source->exists($chemin)) {
                // Ni sur le disque public, ni sur le privé : le fichier a été
                // perdu (déploiement serverless) ou effacé à la main. On le
                // signale sans détacher la colonne — c'est une décision qui
                // revient à l'exploitant, pas à une commande de migration.
                $this->warn("Introuvable, laissé tel quel : {$chemin} (profil #{$profil->id})");
                $absents++;

                continue;
            }

            if (! $sec) {
                $contenu = $source->get($chemin);

                if ($contenu === null) {
                    $this->error("Lecture impossible : {$chemin}");

                    continue;
                }

                $cible->put($chemin, $contenu);
                $source->delete($chemin);
            }

            $this->line(($sec ? '[à faire] ' : '').'Déplacé : '.$chemin);
            $deplaces++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s%d déplacée(s), %d déjà en place, %d introuvable(s).',
            $sec ? '[simulation] ' : '',
            $deplaces,
            $dejaEnPlace,
            $absents
        ));

        if ($sec && $deplaces > 0) {
            $this->comment('Relancez sans --dry-run pour appliquer.');
        }

        return self::SUCCESS;
    }
}
