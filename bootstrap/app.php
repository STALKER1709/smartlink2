<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureCronSecret;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'cron' => EnsureCronSecret::class,
        ]);

        // Le rappel de l'opérateur Mobile Money vient de l'extérieur : il ne
        // peut pas porter de jeton CSRF. Sa légitimité est vérifiée par la
        // signature partagée dans PaymentController::webhook().
        $middleware->preventRequestForgery(except: [
            'payments/webhook',
        ]);

        // Derrière le répartiteur d'un hébergeur (Vercel, Cloudflare, un
        // reverse proxy classique), la requête arrive en HTTP même quand le
        // visiteur est en HTTPS. Sans cette ligne, Laravel génère des URL en
        // http:// : contenu mixte bloqué par le navigateur, et boucle de
        // redirection sur les formulaires. Les en-têtes X-Forwarded-* de
        // l'hébergeur font foi.
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
