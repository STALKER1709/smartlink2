<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Disque des fichiers déposés par les utilisateurs
    |--------------------------------------------------------------------------
    |
    | Photos de profil, logos, images de services. En local « public » suffit :
    | le lien symbolique storage/ les sert. Sur un hébergement au système de
    | fichiers éphémère (Vercel et les plateformes serverless en général), ces
    | fichiers disparaissent d'une requête à l'autre — il faut y pointer
    | MEDIA_DISK sur « s3 ».
    |
    | Les pièces d'identité ne passent pas par ici : voir « id_documents ».
    |
    */

    'media' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Disque des pièces d'identité
    |--------------------------------------------------------------------------
    |
    | Séparé de « media », et jamais public. Un fichier posé sur le disque
    | public est servi par le serveur web sans passer par Laravel : ni
    | middleware, ni Policy. Un nom de fichier aléatoire n'y change rien — ce
    | n'est pas un contrôle d'accès, et une URL qui fuit une fois (capture
    | d'écran, en-tête Referer, journal d'un intermédiaire) ouvre le document
    | définitivement.
    |
    | Ces fichiers ne sortent donc que par
    | `ProviderVerificationController::document()`, qui vérifie la Policy avant
    | de les diffuser.
    |
    */

    'id_documents' => env('ID_DOCUMENTS_DISK', 'id_documents'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Hors de storage/app/public : rien ici n'est atteignable par une URL.
         * `serve` reste à false — même le service de fichiers intégré de
         * Laravel ne doit pas y donner accès sans passer par la Policy.
         */
        'id_documents' => [
            'driver' => 'local',
            'root' => storage_path('app/private/id_documents'),
            'visibility' => 'private',
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        /*
         * Même contenu sur un hébergement sans disque durable. Le seau doit
         * être fermé : ces objets ne sont jamais lus par une URL publique,
         * seulement diffusés par l'application.
         */
        's3_id_documents' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_ID_DOCUMENTS_BUCKET', env('AWS_BUCKET')),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => env('AWS_VISIBILITY', 'public'),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
