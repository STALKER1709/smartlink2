<?php

namespace App\Http\Controllers;

use App\Services\DeploymentCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Déclenche par HTTP les passages que `schedule:run` assure ailleurs.
 *
 * Ces routes ne sont utiles qu'en hébergement serverless (voir
 * DEPLOY-VERCEL.md). Sur un serveur classique, la tâche cron de Laravel
 * décrite dans INSTALL.md reste le bon mécanisme et ces routes peuvent rester
 * inutilisées.
 */
class CronController extends Controller
{
    /**
     * Contrôle d'après-déploiement, en HTTP parce que `php artisan` n'est pas
     * disponible sur un hébergement serverless : c'est le seul moyen de voir
     * ce que l'application constate depuis l'intérieur de la production.
     */
    public function health(DeploymentCheckService $checker): JsonResponse
    {
        $checks = $checker->run();
        $blocking = $checker->hasErrors($checks);

        return response()->json([
            'serverless' => is_serverless(),
            'status' => $blocking ? 'failed' : 'ok',
            'checks' => $checks,
        ], $blocking ? 500 : 200);
    }

    public function subscriptionsRefresh(): JsonResponse
    {
        $status = Artisan::call('subscriptions:refresh');

        return response()->json([
            'command' => 'subscriptions:refresh',
            'status' => $status === 0 ? 'ok' : 'failed',
            'output' => trim(Artisan::output()),
        ], $status === 0 ? 200 : 500);
    }
}
