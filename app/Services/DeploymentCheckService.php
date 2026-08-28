<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Contrôle d'après-déploiement.
 *
 * Trois fonctions de SmartLink cassent sans le moindre message d'erreur quand
 * l'hébergement ne les porte pas : les fichiers déposés disparaissent, les
 * travaux de modération s'empilent sans consommateur, et les abonnements
 * n'expirent jamais. Ce service les vérifie explicitement plutôt que de
 * laisser la panne se découvrir des semaines plus tard.
 *
 * Deux niveaux : « error » bloque (l'application ne fait pas ce qu'elle
 * promet), « warning » signale un réglage volontaire mais notable — un pilote
 * de démonstration, par exemple.
 */
class DeploymentCheckService
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    public function run(): array
    {
        return [
            ...$this->applicationChecks(),
            ...$this->databaseChecks(),
            ...$this->databaseTransportChecks(),
            ...$this->mediaChecks(),
            ...$this->idDocumentChecks(),
            ...$this->queueChecks(),
            ...$this->scheduleChecks(),
            ...$this->assetChecks(),
            ...$this->externalServiceChecks(),
            ...$this->subscriptionChecks(),
        ];
    }

    /**
     * @param  array<int, array{name: string, status: string, message: string}>  $checks
     */
    public function hasErrors(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['status'] === self::ERROR) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function applicationChecks(): array
    {
        $checks = [];

        $checks[] = config('app.key')
            ? $this->ok('APP_KEY', 'Clé de chiffrement présente.')
            : $this->error('APP_KEY', 'Absente : aucune session ni aucun cookie chiffré ne fonctionnera.');

        if (app()->environment('production')) {
            $checks[] = config('app.debug')
                ? $this->error('APP_DEBUG', 'Activé en production : les traces d\'erreur exposent la configuration.')
                : $this->ok('APP_DEBUG', 'Désactivé en production.');

            $checks[] = str_starts_with((string) config('app.url'), 'https://')
                ? $this->ok('APP_URL', (string) config('app.url'))
                : $this->error('APP_URL', 'Doit être en https:// en production : les liens des e-mails et des SMS en dépendent.');
        }

        if (is_serverless()) {
            $checks[] = config('logging.default') === 'stderr'
                ? $this->ok('LOG_CHANNEL', 'stderr : les journaux sont collectés par l\'hébergeur.')
                : $this->warning('LOG_CHANNEL', 'Un journal écrit dans un fichier est perdu à chaque requête. Utiliser stderr.');
        }

        return $checks;
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function databaseChecks(): array
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return [$this->error('Base de données', 'Injoignable : '.$e->getMessage())];
        }

        $checks = [$this->ok('Base de données', 'Connexion établie ('.config('database.default').').')];

        foreach (['users', 'plans', 'subscriptions', 'sessions', 'cache'] as $table) {
            if (! Schema::hasTable($table)) {
                $checks[] = $this->error('Migrations', "Table « {$table} » absente : lancer php artisan migrate --force.");

                return $checks;
            }
        }

        $checks[] = $this->ok('Migrations', 'Les tables attendues sont présentes.');

        // Une migration poussée mais jamais appliquée ne se voit pas : les
        // pages qui n'y touchent pas continuent de répondre, et seule celle
        // qui écrit dans la colonne manquante tombe en 500. C'est ainsi que le
        // flux d'abonnement a cassé après un déploiement.
        $checks[] = $this->pendingMigrations();

        $checks[] = DB::table('plans')->exists()
            ? $this->ok('Formules', 'Au moins une formule d\'abonnement existe.')
            : $this->error('Formules', 'Aucune formule en base : aucun prestataire ne pourra s\'abonner. Lancer php artisan db:seed --force.');

        return $checks;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function pendingMigrations(): array
    {
        try {
            $migrator = app('migrator');
            $chemins = $migrator->paths() ?: [database_path('migrations')];
            $fichiers = array_keys($migrator->getMigrationFiles($chemins));
            $passees = $migrator->getRepository()->getRan();
        } catch (Throwable $e) {
            return $this->warning('Migrations en attente', 'Impossible de le déterminer : '.$e->getMessage());
        }

        $enAttente = array_values(array_diff($fichiers, $passees));

        if ($enAttente === []) {
            return $this->ok('Migrations en attente', 'Aucune : le schéma est à jour.');
        }

        return $this->error(
            'Migrations en attente',
            count($enAttente).' migration(s) poussée(s) mais jamais appliquée(s) — '
            .implode(', ', array_slice($enAttente, 0, 3))
            .(count($enAttente) > 3 ? '…' : '')
            .'. Les écrans qui touchent aux colonnes manquantes répondront 500. Lancer php artisan migrate --force.',
        );
    }

    /**
     * Deux réglages de transport que rien ne signale à l'exécution : une
     * connexion en clair fonctionne parfaitement, et une connexion non
     * mutualisée ne se voit qu'une fois la limite du fournisseur atteinte, en
     * pleine charge.
     *
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function databaseTransportChecks(): array
    {
        $connection = (string) config('database.default');

        if (config("database.connections.{$connection}.driver") !== 'pgsql') {
            return [];
        }

        $checks = [];
        $sslmode = (string) config("database.connections.{$connection}.sslmode");

        $checks[] = in_array($sslmode, ['require', 'verify-ca', 'verify-full'], true)
            ? $this->ok('DB_SSLMODE', "« {$sslmode} » : la connexion est chiffrée.")
            : $this->error(
                'DB_SSLMODE',
                "« {$sslmode} » accepte une connexion en clair vers une base distante. Mettre DB_SSLMODE=require.",
            );

        $host = (string) config("database.connections.{$connection}.host");

        if (is_serverless() && $host !== '' && ! str_contains($host, 'pooler')) {
            $checks[] = $this->warning(
                'Mutualisation',
                "L'hôte « {$host} » n'a pas l'air d'être un pooler. Chaque invocation ouvrant sa propre connexion, la limite du fournisseur est atteinte en pleine charge — passer par le pooler (Supavisor chez Supabase).",
            );
        }

        return $checks;
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function mediaChecks(): array
    {
        $disk = media_disk();
        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver === null) {
            return [$this->error('MEDIA_DISK', "Disque « {$disk} » inconnu dans config/filesystems.php.")];
        }

        if (is_serverless() && $driver === 'local') {
            return [$this->error(
                'MEDIA_DISK',
                "Disque « {$disk} » local sur un hébergement éphémère : chaque photo, logo et pièce d'identité déposés disparaîtront au déploiement suivant, sans erreur.",
            )];
        }

        if ($driver === 's3' && ! config("filesystems.disks.{$disk}.url")) {
            return [$this->warning(
                'MEDIA_DISK',
                "Disque « {$disk} » sans AWS_URL : les URL des images seront construites depuis l'endpoint, ce qui échoue chez la plupart des fournisseurs.",
            )];
        }

        return [$this->ok('MEDIA_DISK', "Disque « {$disk} » ({$driver}).")];
    }

    /**
     * Les pièces d'identité doivent rester hors de portée d'une URL.
     *
     * Ce contrôle existe parce que la faute est invisible : un disque public
     * sert les fichiers sans passer par Laravel, donc sans Policy, et rien
     * dans l'application ne signale l'erreur. Elle ne se voit qu'en essayant
     * l'URL — ce que personne ne fait.
     *
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function idDocumentChecks(): array
    {
        $disk = id_documents_disk();
        $config = config("filesystems.disks.{$disk}");

        if ($config === null) {
            return [$this->error('ID_DOCUMENTS_DISK', "Disque « {$disk} » inconnu dans config/filesystems.php.")];
        }

        if ($disk === media_disk()) {
            return [$this->error(
                'ID_DOCUMENTS_DISK',
                "Les pièces d'identité partagent le disque des images (« {$disk} ») : elles seraient servies par leur URL, sans authentification.",
            )];
        }

        if (($config['visibility'] ?? null) === 'public') {
            return [$this->error(
                'ID_DOCUMENTS_DISK',
                "Disque « {$disk} » en visibilité publique : les pièces d'identité seraient joignables par leur URL, sans authentification.",
            )];
        }

        if (($config['driver'] ?? null) === 'local' && is_serverless()) {
            return [$this->error(
                'ID_DOCUMENTS_DISK',
                "Disque « {$disk} » local sur un hébergement éphémère : les pièces déposées disparaîtront au déploiement suivant et la vérification des prestataires s'arrêtera.",
            )];
        }

        return [$this->ok('ID_DOCUMENTS_DISK', "Disque « {$disk} » privé ({$config['driver']}).")];
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function queueChecks(): array
    {
        $connection = config('queue.default');

        if (is_serverless() && $connection !== 'sync') {
            return [$this->error(
                'QUEUE_CONNECTION',
                "« {$connection} » sur un hébergement sans worker : la modération des annonces et des avis ne s'exécutera jamais. Passer à sync.",
            )];
        }

        if ($connection === 'sync') {
            return [$this->ok('QUEUE_CONNECTION', 'sync : la modération s\'exécute dans la requête.')];
        }

        return [$this->ok('QUEUE_CONNECTION', "« {$connection} » : un queue:work doit tourner en permanence.")];
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function scheduleChecks(): array
    {
        $secret = (string) config('cron.secret');

        if (! is_serverless()) {
            return [$this->ok('Planificateur', 'Hébergement classique : schedule:run assure le passage quotidien.')];
        }

        if ($secret === '') {
            return [$this->error(
                'CRON_SECRET',
                'Absent : /cron/subscriptions-refresh répond 503, donc aucun abonnement n\'expire et aucune relance n\'est envoyée.',
            )];
        }

        return [$this->ok('CRON_SECRET', 'Présent : le passage quotidien peut être déclenché.')];
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function assetChecks(): array
    {
        $manifest = public_path('build/manifest.json');

        return [
            file_exists($manifest)
                ? $this->ok('Assets', 'Le manifeste Vite est présent.')
                : $this->error('Assets', 'public/build/manifest.json absent : toutes les pages tomberont en erreur. Lancer npm run build.'),
        ];
    }

    /**
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function externalServiceChecks(): array
    {
        $checks = [];

        $payment = (string) config('payment.driver');

        if ($payment !== 'hrskills') {
            $checks[] = $this->warning('Paiement', "Pilote « {$payment} » : aucun encaissement réel n'a lieu.");
        } elseif ((string) config('payment.hrskills.webhook_secret') === '') {
            $checks[] = $this->error('Paiement', 'Pilote hrskills sans HRSKILLS_WEBHOOK_SECRET : tous les rappels seront refusés, donc aucun abonnement crédité.');
        } elseif (! str_starts_with((string) config('payment.hrskills.base_url'), 'http')) {
            // Une base vide donne une URL relative, et l'appel échoue au visage
            // du prestataire au moment précis où il paie.
            $checks[] = $this->error('Paiement', "HRSKILLS_BASE_URL n'est pas une URL absolue : chaque encaissement échouera. Vérifier qu'elle n'est pas posée à vide.");
        } else {
            $checks[] = $this->ok('Paiement', 'Pilote hrskills configuré. Détail : php artisan payment:check.');
        }

        $ai = (string) config('ai.driver');
        $checks[] = $ai === 'claude'
            ? ((string) config('ai.api_key') === ''
                ? $this->error('IA', 'Pilote claude sans ANTHROPIC_API_KEY : chaque appel retombera sur le mode par règles.')
                : $this->ok('IA', 'Pilote claude configuré.'))
            : $this->warning('IA', "Pilote « {$ai} » : réponses par règles, aucun coût.");

        return $checks;
    }

    /**
     * Une variable d'environnement présente mais vide annule la valeur par
     * défaut : `(int) ''` vaut zéro. Un cycle de zéro jour laisserait un
     * prestataire régler son abonnement pour rien, sans la moindre erreur.
     *
     * @return array<int, array{name: string, status: string, message: string}>
     */
    private function subscriptionChecks(): array
    {
        $checks = [];

        foreach ([
            'SUBSCRIPTION_CYCLE_DAYS' => ['subscription.cycle_days', "la durée d'un cycle payé"],
            'SUBSCRIPTION_TRIAL_DAYS' => ['subscription.trial_days', "la durée de l'essai gratuit"],
        ] as $variable => [$cle, $quoi]) {
            $jours = (int) config($cle);

            $checks[] = $jours > 0
                ? $this->ok($variable, $jours.' jours.')
                : $this->error($variable, "À zéro : {$quoi} est nulle. Vérifier que la variable n'est pas posée à vide sur l'hébergeur.");
        }

        return $checks;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function ok(string $name, string $message): array
    {
        return ['name' => $name, 'status' => self::OK, 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function warning(string $name, string $message): array
    {
        return ['name' => $name, 'status' => self::WARNING, 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function error(string $name, string $message): array
    {
        return ['name' => $name, 'status' => self::ERROR, 'message' => $message];
    }
}
