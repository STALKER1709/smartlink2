<?php

/*
|--------------------------------------------------------------------------
| Point d'entrée Vercel
|--------------------------------------------------------------------------
|
| Vercel exécute l'application dans une fonction serverless dont le système de
| fichiers est en lecture seule, à l'exception de /tmp. Laravel, lui, écrit
| dans storage/ à chaque requête : vues Blade compilées, verrous de cache,
| journaux. On déplace donc storage/ vers /tmp avant que le framework ne
| démarre — sans ça la première requête échoue sur une écriture refusée.
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

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (getenv('VERCEL') !== false) {
    $storage = '/tmp/storage';

    foreach ([
        $storage.'/app/public',
        $storage.'/framework/cache/data',
        $storage.'/framework/sessions',
        $storage.'/framework/testing',
        $storage.'/framework/views',
        $storage.'/logs',
    ] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    $app->useStoragePath($storage);
}

$app->handleRequest(Request::capture());
