<?php

/*
|--------------------------------------------------------------------------
| Point d'entrée Vercel
|--------------------------------------------------------------------------
|
| Vercel exécute l'application dans une fonction serverless dont le système de
| fichiers est en lecture seule, à l'exception de /tmp. Laravel, lui, écrit à
| deux endroits : dans storage/ à chaque requête (vues compilées, verrous de
| cache, journaux) et dans bootstrap/cache/ au tout premier démarrage (le
| manifeste de ses fournisseurs de services, que le dépôt ne versionne pas).
|
| Les deux sont déplacés vers /tmp ci-dessous. Sans le second, aucun
| fournisseur ne s'enregistre et l'application meurt sur « Target class [view]
| does not exist » — un message qui ne dit rien de la vraie cause.
|
| /tmp est partagé entre les requêtes d'une même instance chaude, mais perdu
| au démarrage d'une nouvelle : rien de durable ne doit y vivre. Les fichiers
| déposés par les utilisateurs passent par MEDIA_DISK=s3 (voir
| DEPLOY-VERCEL.md), et les journaux par LOG_CHANNEL=stderr, que Vercel
| collecte.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$serverless = getenv('VERCEL') !== false;

// Avant la construction de l'application : les chemins de cache sont lus au
// démarrage, et le dépôt de variables d'environnement est immuable ensuite.
if ($serverless) {
    serverless_relocate_bootstrap_cache('/tmp/bootstrap/cache', __DIR__.'/../bootstrap/cache');
}

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if ($serverless) {
    $app->useStoragePath(serverless_storage_path('/tmp/storage'));
}

$app->handleRequest(Request::capture());
