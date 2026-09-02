<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protège les routes que le planificateur de l'hébergeur appelle en HTTP.
 *
 * Refuse par défaut : tant qu'aucun secret n'est configuré, la route est
 * fermée (503) plutôt qu'ouverte à tout le monde.
 */
class EnsureCronSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('cron.secret');

        if (! is_string($secret) || $secret === '') {
            abort(503, 'Tâche planifiée non configurée : CRON_SECRET est absent.');
        }

        $presented = $request->bearerToken() ?? '';

        if (! hash_equals($secret, $presented)) {
            abort(403);
        }

        return $next($request);
    }
}
