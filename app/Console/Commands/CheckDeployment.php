<?php

namespace App\Console\Commands;

use App\Services\DeploymentCheckService;
use Illuminate\Console\Command;

/**
 * À lancer après chaque déploiement, et en particulier après un déploiement
 * serverless où trois fonctions cassent sans message d'erreur.
 */
class CheckDeployment extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Vérifie que l\'hébergement porte réellement toutes les fonctions de l\'application';

    public function handle(DeploymentCheckService $checker): int
    {
        $checks = $checker->run();

        $this->line(is_serverless()
            ? 'Hébergement détecté : serverless (disque éphémère, aucun processus continu).'
            : 'Hébergement détecté : serveur classique.');
        $this->newLine();

        foreach ($checks as $check) {
            $line = str_pad($check['name'], 20).' '.$check['message'];

            match ($check['status']) {
                DeploymentCheckService::OK => $this->info('  ✔  '.$line),
                DeploymentCheckService::WARNING => $this->warn('  !  '.$line),
                default => $this->error('  ✖  '.$line),
            };
        }

        $this->newLine();

        if ($checker->hasErrors($checks)) {
            $this->error('Des points bloquants restent à régler.');

            return self::FAILURE;
        }

        $this->info('Aucun point bloquant.');

        return self::SUCCESS;
    }
}
