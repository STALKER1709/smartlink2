<?php

namespace App\Support;

/**
 * Traduit l'échec d'une requête sortante en cause probable et en remède.
 *
 * `ConnectionException` est un nom de classe, pas un diagnostic : il recouvre
 * un certificat racine absent, un DNS muet, un proxy d'entreprise et une
 * machine hors ligne, qui ne se corrigent pas de la même façon. Les commandes
 * `images:check` et `images:credits` l'affichaient tel quel — on savait que
 * ça ne marchait pas, jamais pourquoi.
 */
class HttpDiagnostic
{
    /**
     * Le conseil qui correspond au message d'erreur, ou null.
     */
    public static function conseil(string $message): ?string
    {
        $m = mb_strtolower($message);

        // Le cas de très loin le plus fréquent sous Windows : PHP y est livré
        // sans magasin de certificats racine, et *toute* requête HTTPS échoue
        // — alors que le navigateur et Git, qui ont le leur, passent très bien.
        if (str_contains($m, 'ssl certificate problem')
            || str_contains($m, 'unable to get local issuer')
            || str_contains($m, 'curl error 60')
            || str_contains($m, 'cafile')
            || str_contains($m, 'certificate verify failed')) {
            return <<<'TXT'
            PHP n'a pas de magasin de certificats racine — c'est le cas par défaut sous
            Windows, et cela fait échouer toute requête HTTPS, pas seulement celle-ci.
            Le navigateur et Git ont le leur, ce qui explique qu'eux passent.

            Le remède, une fois pour toutes :
              1. Télécharger https://curl.se/ca/cacert.pem
              2. L'enregistrer, par exemple, dans C:\php\extras\ssl\cacert.pem
              3. Dans php.ini, décommenter et renseigner les deux lignes :
                     curl.cainfo = "C:\php\extras\ssl\cacert.pem"
                     openssl.cafile = "C:\php\extras\ssl\cacert.pem"
              4. Rouvrir le terminal, puis relancer la commande.

            Ne désactivez pas la vérification TLS pour contourner : elle est ce qui
            distingue une réponse de Wikimedia d'une réponse de n'importe qui d'autre.
            TXT;
        }

        if (str_contains($m, 'could not resolve host') || str_contains($m, 'curl error 6')) {
            return 'Le nom d\'hôte n\'est pas résolu : DNS injoignable, ou machine hors ligne.';
        }

        if (str_contains($m, 'connect tunnel failed')
            || str_contains($m, 'curl error 56')
            || str_contains($m, 'proxy')) {
            return 'Un proxy s\'interpose et refuse la connexion vers Wikimedia. Si votre '
                .'réseau en impose un, renseignez HTTPS_PROXY dans l\'environnement ; sinon, '
                .'demandez l\'ouverture de commons.wikimedia.org, ou lancez la commande depuis '
                .'un réseau qui ne filtre pas.';
        }

        if (str_contains($m, 'connection refused') || str_contains($m, 'curl error 7')) {
            return 'La connexion est refusée : un pare-feu ou un proxy s\'interpose. '
                .'Renseignez HTTPS_PROXY dans l\'environnement si votre réseau en impose un.';
        }

        if (str_contains($m, 'timed out') || str_contains($m, 'curl error 28')) {
            return 'Délai dépassé : réseau très lent, ou proxy qui ne répond pas.';
        }

        return null;
    }

    /**
     * Le message d'une exception, ramené à une ligne lisible.
     */
    public static function resume(\Throwable $e, int $longueur = 160): string
    {
        $message = preg_replace('/\s+/', ' ', trim($e->getMessage())) ?? '';

        if ($message === '') {
            return class_basename($e);
        }

        return mb_strlen($message) > $longueur
            ? mb_substr($message, 0, $longueur).'…'
            : $message;
    }
}
