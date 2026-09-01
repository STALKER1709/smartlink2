<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

if (! function_exists('media_disk')) {
    /**
     * Le disque qui porte les fichiers déposés destinés à être vus (photos de
     * profil, logos, images de services).
     *
     * En local c'est « public », servi par le lien symbolique storage/. Sur un
     * hébergement au système de fichiers éphémère — Vercel, par exemple — les
     * fichiers écrits par une requête n'existent plus à la suivante : il faut
     * alors pointer MEDIA_DISK sur « s3 » (ou tout stockage compatible S3),
     * sinon chaque image déposée est perdue sans le moindre message d'erreur.
     *
     * ⚠️ Les pièces d'identité n'ont rien à faire ici : ce disque est public,
     * donc servi sans passer par Laravel. Voir `id_documents_disk()`.
     */
    function media_disk(): string
    {
        $disk = config('filesystems.media');

        return is_string($disk) && $disk !== '' ? $disk : 'public';
    }
}

if (! function_exists('id_documents_disk')) {
    /**
     * Le disque qui porte les pièces d'identité, toujours privé.
     *
     * Rien de ce qui y est écrit n'est atteignable par une URL : ces documents
     * ne sortent que par une route qui vérifie la Policy. Sur un hébergement
     * sans disque durable, pointer ID_DOCUMENTS_DISK sur « s3_id_documents »,
     * dont le seau doit rester fermé.
     */
    function id_documents_disk(): string
    {
        $disk = config('filesystems.id_documents');

        return is_string($disk) && $disk !== '' ? $disk : 'id_documents';
    }
}

if (! function_exists('media_url')) {
    /**
     * URL publique d'un fichier déposé, quel que soit le disque configuré.
     *
     * Renvoie null pour un chemin vide, ce qui laisse les vues garder leur
     * garde `@if` habituelle avant d'afficher une image.
     *
     * ⚠️ Renvoie également null quand le disque est mal configuré, au lieu de
     * laisser remonter l'exception. Un `MEDIA_DISK=s3` sans `AWS_BUCKET` fait
     * lever au SDK « The GetObject operation requires non-empty parameter:
     * Bucket » — depuis une vue, donc au milieu du rendu, donc en erreur 500.
     * Les pages sans image répondent encore : le site paraît à moitié vivant
     * et le journal ne parle que d'un paramètre d'API. C'est arrivé en
     * production, et l'accueil est resté en 500 tant que la variable est
     * restée vide. Une vignette manquante est un défaut ; une page morte en
     * est un autre, sans commune mesure.
     *
     * La configuration reste fautive, et `deploy:check` la refuse ; ce garde
     * ne fait que borner ce qu'elle coûte le temps qu'on la corrige.
     */
    function media_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            return Storage::disk(media_disk())->url($path);
        } catch (Throwable $e) {
            // Une fois par processus : appelée par chaque vignette d'une
            // liste, elle remplirait le journal de la même ligne.
            static $signale = false;

            if (! $signale) {
                $signale = true;
                Log::warning('Disque de médias inutilisable : '.$e->getMessage(), [
                    'disque' => media_disk(),
                ]);
            }

            return null;
        }
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

if (! function_exists('image_photo')) {
    /**
     * Une entrée de `config/imagery.php`, normalisée, ou null.
     *
     * L'entrée est passée telle quelle plutôt que désignée par une clé
     * pointée : un nom de métier peut contenir un point, et `data_get` l'aurait
     * alors coupé en deux niveaux inexistants.
     *
     * Renvoie toujours null quand `REMOTE_IMAGES` est à faux : les vues gardent
     * alors leur illustration dessinée sans avoir à connaître le réglage.
     *
     * @return array{url: string, credit: string, source: ?string}|null
     */
    function image_photo(?array $entree): ?array
    {
        if (! config('imagery.enabled')) {
            return null;
        }

        if (! is_array($entree) || ! is_string($entree['url'] ?? null) || $entree['url'] === '') {
            return null;
        }

        // Les deux formes d'URL : complète, ou chemin sur le disque de médias.
        // Sans cela, une photo rapatriée par `photos:import` aurait demandé une
        // seconde table, et les deux auraient divergé.
        $url = str_starts_with($entree['url'], 'http')
            ? $entree['url']
            : media_url($entree['url']);

        if ($url === null) {
            return null;
        }

        $auteur = $entree['auteur'] ?? null;
        $licence = $entree['licence'] ?? null;

        // Pas de mention, pas d'affichage. La règle est tenue ici et non par un
        // seul test : une entrée incomplète peut être déclarée — c'est le cas
        // des propositions à relire — et le commutateur, lui, s'actionne d'un
        // mot. Sans ce garde-fou, une photo d'un auteur qu'on ne nomme pas
        // partirait en ligne sur un simple `REMOTE_IMAGES=true`.
        if (! is_string($auteur) || $auteur === '' || ! is_string($licence) || $licence === '') {
            return null;
        }

        return [
            'url' => $url,
            'credit' => trim($auteur.' — '.$licence),
            'source' => $entree['source'] ?? null,
        ];
    }
}

if (! function_exists('image_categorie')) {
    /**
     * La photographie d'un métier, ou null.
     *
     * La table est indexée par le nom de la catégorie tel qu'il est en base.
     * Une catégorie absente n'est pas une erreur : elle garde son illustration.
     *
     * @return array{url: string, credit: string, source: ?string}|null
     */
    function image_categorie(?string $nomCategorie): ?array
    {
        if ($nomCategorie === null) {
            return null;
        }

        return image_photo(config('imagery.categories')[$nomCategorie] ?? null);
    }
}
