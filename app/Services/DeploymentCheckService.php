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
            ...$this->mediaChecks(),
            ...$this->queueChecks(),
            ...$this->scheduleChecks(),
            ...$this->assetChecks(),
            ...$this->externalServiceChecks(),
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

        $checks[] = DB::table('plans')->exists()
            ? $this->ok('Formules', 'Au moins une formule d\'abonnement existe.')
            : $this->error('Formules', 'Aucune formule en base : aucun prestataire ne pourra s\'abonner. Lancer php artisan db:seed --force.');

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
        $checks[] = $payment === 'hrskills'
            ? ((string) config('payment.hrskills.webhook_secret') === ''
                ? $this->error('Paiement', 'Pilote hrskills sans HRSKILLS_WEBHOOK_SECRET : tous les rappels seront refusés, donc aucun abonnement crédité.')
                : $this->ok('Paiement', 'Pilote hrskills configuré. Détail : php artisan payment:check.'))
            : $this->warning('Paiement', "Pilote « {$payment} » : aucun encaissement réel n'a lieu.");

        $ai = (string) config('ai.driver');
        $checks[] = $ai === 'claude'
            ? ((string) config('ai.api_key') === ''
                ? $this->error('IA', 'Pilote claude sans ANTHROPIC_API_KEY : chaque appel retombera sur le mode par règles.')
                : $this->ok('IA', 'Pilote claude configuré.'))
            : $this->warning('IA', "Pilote « {$ai} » : réponses par règles, aucun coût.");

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
