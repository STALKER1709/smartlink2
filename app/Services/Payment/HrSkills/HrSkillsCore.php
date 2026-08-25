<?php

namespace App\Services\Payment\HrSkills;

/**
 * Logique pure de l'intégration HR-Skills Pay, testable sans réseau.
 *
 * Portée depuis le projet NILE, où elle est éprouvée en bac à sable. Trois
 * points ne sont pas spécifiés par la documentation « HR-Skills Pay API v1 »
 * et sont traités défensivement plutôt que devinés :
 *   1. la casse attendue du champ « operator » ;
 *   2. la structure du corps des rappels, dont aucun exemple n'est publié ;
 *   3. le nom exact de l'en-tête de signature.
 */
class HrSkillsCore
{
    /** Opérateurs Mobile Money disponibles au Cameroun. */
    public const OPERATORS = ['mtn', 'orange'];

    /**
     * Clé d'idempotence d'un encaissement, DÉTERMINISTE : deux tentatives
     * portant sur le même paiement produisent la même clé.
     *
     * C'est tout l'intérêt de l'en-tête. Une clé tirée au hasard à chaque
     * appel ne protège de rien : le vrai risque de double débit est qu'un
     * prestataire relance un paiement resté en attente après une réponse
     * perdue, et sans clé stable l'API y voit un second encaissement.
     *
     * Nos références (« SL-XXXX ») ne sont pas des UUID : on en dérive une
     * empreinte mise à la forme d'un UUID v4, format annoncé par la
     * documentation. Le déterminisme est la seule propriété qui compte.
     */
    public static function idempotencyKey(string $reference): string
    {
        if (self::isUuidV4($reference)) {
            return strtolower($reference);
        }

        $digest = hash('sha256', 'smartlink:hrskills:'.$reference);
        $chars = str_split(substr($digest, 0, 32));

        // Version 4 et variante RFC 4122, pour un UUID v4 valide.
        $chars[12] = '4';
        $chars[16] = substr('89ab', hexdec($digest[16]) % 4, 1);
        $s = implode('', $chars);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($s, 0, 8), substr($s, 8, 4), substr($s, 12, 4),
            substr($s, 16, 4), substr($s, 20, 12),
        );
    }

    /**
     * Empreinte d'une clé, pour un diagnostic lisible sans divulguer le secret.
     *
     * Le préfixe (« hrsk_sk_test_… ») n'est pas secret et c'est lui qui trahit
     * les deux erreurs de configuration les plus fréquentes : deux clés
     * interverties, ou une clé de test là où l'on croyait être en production.
     * La longueur, elle, trahit une copie tronquée.
     */
    public static function maskKey(string $key): string
    {
        if ($key === '') {
            return '(vide)';
        }

        $longueur = mb_strlen($key);
        $empreinte = $longueur <= 8
            ? str_repeat('•', $longueur)
            : mb_substr($key, 0, 12).'…'.mb_substr($key, -4);

        return $empreinte." ({$longueur} caractères)";
    }

    public static function isUuidV4(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    /**
     * Normalise l'opérateur en minuscules.
     *
     * La documentation se contredit : le tableau des paramètres liste
     * « mtn · orange » en minuscules, l'exemple cURL de la même section envoie
     * « ORANGE ». On s'aligne sur le tableau, qui est la partie normative.
     */
    public static function normalizeOperator(string $value): string
    {
        return strtolower(trim($value));
    }

    public static function isValidOperator(string $value): bool
    {
        return in_array(self::normalizeOperator($value), self::OPERATORS, true);
    }

    /**
     * Numéro au format attendu : indicatif pays inclus, sans « + » ni espaces
     * (237655500393). Renvoie null si le numéro n'est pas exploitable.
     */
    public static function normalizePhone(string $phone, string $countryCode = '237'): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) < 8) {
            return null;
        }

        return str_starts_with($digits, $countryCode) ? $digits : $countryCode.$digits;
    }

    /**
     * Traduit un statut de l'API en issue définitive, ou null.
     *
     * « HOLD » signifie « bloqué par contrôle anti-blanchiment, en révision
     * manuelle » : ni succès ni échec définitif. Le laisser en attente évite
     * de créditer un abonnement qui peut encore être refusé.
     */
    public static function mapStatus(string $status): ?string
    {
        return match (strtoupper(trim($status))) {
            'SUCCESS' => 'success',
            'FAILED' => 'failed',
            // Remboursement : un échec du point de vue de l'abonnement.
            'REFUNDED' => 'failed',
            // PENDING, HOLD, ou inconnu : rien de définitif.
            default => null,
        };
    }

    /**
     * Environnement porté par une clé, lu sur son préfixe (hrsk_pk_live_…,
     * hrsk_sk_test_…). C'est la seule chose qui distingue le bac à sable de la
     * production : l'hôte de l'API est le même des deux côtés.
     *
     * Renvoie null si la clé ne porte aucun marqueur — traité comme une erreur
     * de configuration, jamais comme « production » par défaut : partir en
     * production sur une clé illisible est le pire des deux échecs.
     */
    public static function keyEnvironment(string $key): ?string
    {
        if (str_contains($key, '_test_')) {
            return 'test';
        }

        if (str_contains($key, '_live_')) {
            return 'live';
        }

        return null;
    }

    public static function isTestKey(string $key): bool
    {
        return self::keyEnvironment($key) === 'test';
    }

    /**
     * Les deux clés désignent-elles le même environnement ?
     *
     * Elles sont copiées séparément depuis le tableau de bord : rien n'empêche
     * d'associer une clé A de production à une clé B de test. L'application
     * choisissant son chemin d'après la clé A, un tel mélange enverrait des
     * appels de production authentifiés par un secret de test.
     */
    public static function keysAreCoherent(string $keyA, string $keyB): bool
    {
        $a = self::keyEnvironment($keyA);

        return $a !== null && $a === self::keyEnvironment($keyB);
    }

    /**
     * Racine des appels qui consomment le token de transaction.
     *
     * En test, ces appels ne sont servis que sous « /sandbox » ; ailleurs
     * l'API renvoie un 403. Ce préfixe ne vient pas de la documentation mais
     * du refus renvoyé par l'API, et il est confirmé en bac à sable. Rien ne
     * le documentant, il reste susceptible de disparaître : si les
     * encaissements de test répondaient un jour 404, c'est ici qu'il faut
     * regarder en premier.
     */
    public static function apiRoot(string $baseUrl, string $keyA): string
    {
        $base = rtrim($baseUrl, '/');

        if (! self::isTestKey($keyA)) {
            return $base;
        }

        return str_ends_with($base, '/sandbox') ? $base : $base.'/sandbox';
    }

    /**
     * Vérifie la signature d'un rappel : HMAC-SHA256 du corps BRUT, comparé
     * en temps constant.
     *
     * La documentation nomme l'en-tête « X-Hub-Signature ». La convention la
     * plus répandue étant « X-Hub-Signature-256 » et aucun échantillon de
     * requête n'étant publié, les deux sont acceptés.
     *
     * @param  array<int, string|null>  $signatures
     */
    public static function verifySignature(string $secret, string $rawBody, array $signatures): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);
        $valid = false;

        foreach ($signatures as $signature) {
            if ($signature === null || $signature === '') {
                continue;
            }

            // Pas de court-circuit : toutes les variantes sont comparées à
            // chaque appel, pour que la durée ne révèle pas laquelle a
            // correspondu.
            if (hash_equals($expected, trim($signature))) {
                $valid = true;
            }
        }

        return $valid;
    }

    /**
     * Extrait ce qui nous est utile d'un corps de rappel.
     *
     * Aucun exemple de charge utile n'est publié : ni le nom exact des champs,
     * ni leur imbrication ne sont connus. La lecture est donc tolérante — la
     * référence est cherchée à la racine comme sous « data ».
     *
     * @return array{reference: string, internal_reference: ?string}|null
     */
    public static function readWebhookPayload(mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $reference = self::text($data['reference'] ?? null)
            ?? self::text($payload['reference'] ?? null)
            ?? self::text($data['transaction_reference'] ?? null)
            ?? self::text($payload['transaction_reference'] ?? null);

        if ($reference === null) {
            return null;
        }

        $metadata = is_array($data['metadata'] ?? null)
            ? $data['metadata']
            : (is_array($payload['metadata'] ?? null) ? $payload['metadata'] : []);

        return [
            'reference' => $reference,
            'internal_reference' => self::text($metadata['reference_interne'] ?? null)
                ?? self::text($metadata['reference'] ?? null),
        ];
    }

    /**
     * Squelette d'une charge JSON : ses CLÉS, jamais ses valeurs.
     *
     * Quand la lecture d'un rappel échoue, c'est que le fournisseur nomme ou
     * imbrique ses champs autrement que prévu — et sans voir la structure
     * reçue, on en serait réduit à deviner sur une API de paiement. Seules les
     * clés sont restituées : un corps de rappel transporte un numéro de
     * téléphone et un montant, et un journal se relit et se copie.
     */
    public static function jsonShape(mixed $value, int $depth = 3): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_array($value) && array_is_list($value)) {
            if ($value === []) {
                return '[]';
            }

            return $depth <= 0 ? '[…]' : '['.count($value).'× '.self::jsonShape($value[0], $depth - 1).']';
        }

        if (! is_array($value)) {
            return get_debug_type($value);
        }

        if ($depth <= 0) {
            return '{…}';
        }

        $entries = [];
        foreach ($value as $key => $item) {
            $entries[] = is_array($item)
                ? $key.': '.self::jsonShape($item, $depth - 1)
                : (string) $key;
        }

        return '{'.implode(', ', $entries).'}';
    }

    /**
     * Décrit un corps de rappel inanalysable, SANS ses valeurs.
     *
     * Ce cas est muet par nature : la signature est valide, donc la charge est
     * authentique, mais elle ne se laisse pas lire. Sans description, trois
     * situations qui appellent trois réponses différentes deviennent
     * indiscernables : un corps vide (un simple test depuis le tableau de
     * bord), un corps encodé en formulaire, ou un JSON réellement malformé.
     */
    public static function bodyDiagnostic(string $raw, string $contentType): string
    {
        $type = trim(explode(';', $contentType)[0]) ?: '(absent)';
        $parts = ['longueur='.strlen($raw), 'type='.$type];

        if ($raw === '') {
            $parts[] = 'corps VIDE';

            return implode(' · ', $parts);
        }

        $parts[] = 'débute par '.json_encode($raw[0]);

        if (! str_starts_with($raw, '{') && ! str_starts_with($raw, '[') && str_contains($raw, '=')) {
            parse_str($raw, $form);

            if ($form !== []) {
                $parts[] = 'ressemble à un formulaire · champs: '.implode(', ', array_keys($form));
            }
        }

        return implode(' · ', $parts);
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
