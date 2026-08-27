<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('media_disk')) {
    /**
     * Le disque qui porte les fichiers déposés par les utilisateurs (photos de
     * profil, logos, images de services, pièces d'identité).
     *
     * En local c'est « public », servi par le lien symbolique storage/. Sur un
     * hébergement au système de fichiers éphémère — Vercel, par exemple — les
     * fichiers écrits par une requête n'existent plus à la suivante : il faut
     * alors pointer MEDIA_DISK sur « s3 » (ou tout stockage compatible S3),
     * sinon chaque image déposée est perdue sans le moindre message d'erreur.
     */
    function media_disk(): string
    {
        $disk = config('filesystems.media');

        return is_string($disk) && $disk !== '' ? $disk : 'public';
    }
}

if (! function_exists('media_url')) {
    /**
     * URL publique d'un fichier déposé, quel que soit le disque configuré.
     *
     * Renvoie null pour un chemin vide, ce qui laisse les vues garder leur
     * garde `@if` habituelle avant d'afficher une image.
     */
    function media_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk(media_disk())->url($path);
    }
}

if (! function_exists('is_serverless')) {
    /**
     * L'application tourne-t-elle dans une fonction éphémère ?
     *
     * Vercel expose la variable VERCEL dans l'environnement d'exécution. Ce
     * qui en dépend n'est jamais une question de goût : sans disque durable,
     * sans worker et sans cron, trois fonctions cassent en silence.
     */
    function is_serverless(): bool
    {
        return getenv('VERCEL') !== false || env('SERVERLESS', false) === true;
    }
}

if (! function_exists('serverless_relocate_bootstrap_cache')) {
    /**
     * Déplace vers un dossier écrivable les cinq fichiers de cache que Laravel
     * écrit dans bootstrap/cache/.
     *
     * Sur un hébergement serverless, ce dossier est en lecture seule, et
     * `services.php` — le manifeste des fournisseurs de services — n'est pas
     * versionné : Laravel tente de l'écrire à la première requête, échoue,
     * n'enregistre aucun fournisseur, et meurt sur « Target class [view] does
     * not exist », un message qui ne dit rien de la vraie cause.
     *
     * À appeler AVANT la construction de l'application : les chemins sont lus
     * au démarrage et le dépôt de variables d'environnement est immuable.
     *
     * @return array<string, string> les chemins posés, par variable
     */
    function serverless_relocate_bootstrap_cache(string $root, ?string $builtCacheDir = null): array
    {
        if (! is_dir($root)) {
            mkdir($root, 0755, true);
        }

        // Ce que la construction a déjà produit — package:discover écrit
        // packages.php — est repris plutôt que recalculé à chaque démarrage.
        foreach (glob(rtrim((string) $builtCacheDir, '/').'/*.php') ?: [] as $built) {
            $target = $root.'/'.basename($built);

            if (! file_exists($target)) {
                copy($built, $target);
            }
        }

        $paths = [
            'APP_SERVICES_CACHE' => $root.'/services.php',
            'APP_PACKAGES_CACHE' => $root.'/packages.php',
            'APP_CONFIG_CACHE' => $root.'/config.php',
            'APP_ROUTES_CACHE' => $root.'/routes-v7.php',
            'APP_EVENTS_CACHE' => $root.'/events.php',
        ];

        foreach ($paths as $key => $path) {
            $_ENV[$key] = $path;
            $_SERVER[$key] = $path;
            putenv("{$key}={$path}");
        }

        return $paths;
    }
}

if (! function_exists('serverless_storage_path')) {
    /**
     * Prépare un storage/ écrivable et renvoie sa racine.
     *
     * Laravel y écrit à chaque requête : vues Blade compilées, verrous de
     * cache, journaux. Rien n'y est durable — /tmp est partagé entre les
     * requêtes d'une instance chaude, perdu au démarrage de la suivante.
     */
    function serverless_storage_path(string $root): string
    {
        foreach ([
            $root.'/app/public',
            $root.'/framework/cache/data',
            $root.'/framework/sessions',
            $root.'/framework/testing',
            $root.'/framework/views',
            $root.'/logs',
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }

        return $root;
    }
}

if (! function_exists('env_or')) {
    /**
     * Lit une variable d'environnement en traitant « vide » comme « absente ».
     *
     * `env('X', 30)` ne rend 30 que si X n'existe pas. Si X existe et vaut la
     * chaîne vide — ce qui arrive dès qu'on colle un bloc .env sans remplir
     * toutes les lignes — la valeur par défaut est ignorée et l'on récupère ''.
     * Sur un réglage numérique, `(int) ''` vaut 0 : un abonnement prolongé de
     * zéro jour, sans la moindre erreur.
     */
    function env_or(string $cle, mixed $defaut = null): mixed
    {
        $valeur = env($cle);

        return ($valeur === null || $valeur === '') ? $defaut : $valeur;
    }
}

if (! function_exists('image_distante')) {
    /**
     * URL d'un bandeau servi par un hôte extérieur, ou null.
     *
     * Renvoie toujours null quand `REMOTE_IMAGES` est à faux : les vues gardent
     * alors leur repli sans avoir à connaître le réglage.
     */
    function image_distante(string $cle): ?string
    {
        if (! config('imagery.enabled')) {
            return null;
        }

        $url = config('imagery.'.$cle);

        return is_string($url) && $url !== '' ? $url : null;
    }
}

if (! function_exists('image_categorie')) {
    /**
     * URL de la photographie distante d'un métier, ou null.
     *
     * La table est indexée par le nom de la catégorie tel qu'il est en base.
     * Une catégorie absente n'est pas une erreur : elle garde son pictogramme.
     */
    function image_categorie(?string $nomCategorie): ?string
    {
        if ($nomCategorie === null || ! config('imagery.enabled')) {
            return null;
        }

        $url = config('imagery.categories')[$nomCategorie] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
