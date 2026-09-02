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

        /*
         * ...et parce qu'on fait confiance à tous les répartiteurs, il faut
         * dire lesquels des noms d'hôte annoncés sont légitimes.
         *
         * Sans cette liste, Laravel fabrique ses URL absolues à partir de
         * l'en-tête « Host » (ou « X-Forwarded-Host », honoré du fait de la
         * ligne au-dessus) — c'est-à-dire à partir d'une valeur que le client
         * choisit. Une demande de mot de passe oublié envoyée avec un Host
         * falsifié fait donc partir, vers la boîte du titulaire, un lien de
         * réinitialisation valide qui pointe chez l'attaquant : il suffit que
         * la victime clique pour que le jeton lui soit livré.
         *
         * Laravel n'applique cette liste ni en local ni sous tests, et une
         * liste vide ne restreint rien : un APP_URL sans hôte revient au
         * comportement d'avant plutôt que de fermer le site. C'est pour ça que
         * `deploy:check` contrôle APP_URL séparément.
         */
        $middleware->trustHosts(at: fn () => array_filter([
            // Les déploiements de prévisualisation portent un nom tiré au sort
            // à chaque poussée : ils ne peuvent pas être nommés un par un.
            is_serverless() ? '^(.+\\.)?vercel\\.app$' : null,
        ]), subdomains: true);

        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
