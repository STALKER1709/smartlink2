<?php

namespace App\Http\Controllers;

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
