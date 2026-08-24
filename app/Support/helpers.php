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
