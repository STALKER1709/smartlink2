<?php

namespace App\Console\Commands;

use App\Services\Payment\HrSkills\HrSkillsCore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Vérifie la configuration d'encaissement avant qu'un vrai paiement ne la
 * mette à l'épreuve. À lancer après chaque déploiement.
 */
class CheckPaymentConfiguration extends Command
{
    protected $signature = 'payment:check {--live : Tente réellement l\'échange de token auprès de HR-Skills}';

    protected $description = 'Vérifie la cohérence de la configuration Mobile Money';

    public function handle(): int
    {
        $driver = (string) config('payment.driver');

        if ($driver !== 'hrskills') {
            $this->warn("Pilote « {$driver} » : aucun encaissement réel n'a lieu.");

            return self::SUCCESS;
        }

        $keyA = (string) config('payment.hrskills.key_a');
        $keyB = (string) config('payment.hrskills.key_b');
        $secret = (string) config('payment.hrskills.webhook_secret');
        $problems = [];

        $this->line('Clé A : '.HrSkillsCore::maskKey($keyA));
        $this->line('Clé B : '.HrSkillsCore::maskKey($keyB));

        // Un retour à la ligne capturé en collant la clé dans l'interface de
        // l'hébergeur ne se voit nulle part et fait répondre « clé inconnue ».
        foreach (['HRSKILLS_CLE_A' => $keyA, 'HRSKILLS_CLE_B' => $keyB] as $nom => $valeur) {
            if ($valeur !== '' && trim($valeur) !== $valeur) {
                $problems[] = "{$nom} contient une espace ou un retour à la ligne en début ou en fin : "
                    .'la clé sera refusée telle quelle.';
            }
        }

        if ($keyA === '' || $keyB === '') {
            $problems[] = 'HRSKILLS_CLE_A et HRSKILLS_CLE_B sont requis avec le pilote hrskills.';
        } else {
            $envA = HrSkillsCore::keyEnvironment($keyA);
            $envB = HrSkillsCore::keyEnvironment($keyB);

            if ($envA === null || $envB === null) {
                // Une clé illisible n'est jamais traitée comme « production » :
                // partir en live sur une clé qu'on ne sait pas lire est le pire
                // des deux échecs.
                $problems[] = 'Clé illisible : le préfixe doit contenir _test_ ou _live_.';
            } elseif ($envA !== $envB) {
                $problems[] = "Environnements incohérents : clé A en « {$envA} », clé B en « {$envB} ». "
                    .'Les deux doivent porter le même environnement.';
            } else {
                $racine = HrSkillsCore::apiRoot((string) config('payment.hrskills.base_url'), $keyA);

                if (! str_starts_with($racine, 'http')) {
                    $problems[] = "Racine des appels « {$racine} » : ce n'est pas une URL absolue. "
                        ."HRSKILLS_BASE_URL est probablement posée à vide sur l'hébergeur — "
                        .'une variable vide annule la valeur par défaut.';
                } else {
                    $this->info("Clés cohérentes, environnement « {$envA} ».");
                    $this->line('Racine des appels : '.$racine);

                    if ($this->option('live')) {
                        $problems = array_merge($problems, $this->probe());
                    } else {
                        $this->comment('Cohérence de forme seulement. Pour éprouver les clés '
                            .'auprès de HR-Skills : php artisan payment:check --live');
                    }
                }
            }
        }

        if ($secret === '') {
            $problems[] = 'HRSKILLS_WEBHOOK_SECRET absent : les rappels seront tous refusés, '
                .'donc aucun abonnement ne sera jamais crédité.';
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Éprouve réellement les clés : rien d'autre ne distingue une clé bien
     * formée d'une clé que HR-Skills reconnaît.
     *
     * L'échange de token est tenté sur la racine nue puis, si elle échoue avec
     * une clé de test, sous « /sandbox ». Ce second essai est un diagnostic,
     * pas un repli : la documentation ne dit pas où vit ce point d'entrée en
     * bac à sable, et si c'est celui-là qui répond, c'est le fournisseur qu'il
     * faut corriger, pas ce diagnostic qu'il faut garder.
     *
     * @return array<int, string>
     */
    private function probe(): array
    {
        $keyA = (string) config('payment.hrskills.key_a');
        $base = rtrim((string) config('payment.hrskills.base_url'), '/');
        $candidats = [$base];

        if (HrSkillsCore::isTestKey($keyA) && ! str_ends_with($base, '/sandbox')) {
            $candidats[] = $base.'/sandbox';
        }

        $echecs = [];

        foreach ($candidats as $racine) {
            $url = $racine.'/v1/auth/transaction-token';

            try {
                $response = Http::timeout(15)
                    ->withHeaders(['Authorization' => 'Bearer '.$keyA, 'Accept' => 'application/json'])
                    ->post($url, ['api_secret' => (string) config('payment.hrskills.key_b')]);
            } catch (\Throwable $e) {
                $echecs[] = "{$url} : injoignable · ".$e->getMessage();

                continue;
            }

            if ($response->successful() && is_string($response->json('transaction_token'))) {
                $this->info("Token obtenu sur {$url} : les clés sont reconnues.");

                if ($racine !== $base) {
                    return ["Le token n'a été obtenu que sous « {$racine} », pas sur « {$base} ». "
                        .'HrSkillsPayProvider::transactionToken() appelle la racine nue : '
                        .'il faut la corriger pour que les encaissements de test fonctionnent.'];
                }

                return [];
            }

            $echecs[] = "{$url} : HTTP ".$response->status().' · '.mb_substr($response->body(), 0, 200);
        }

        return array_merge(
            ['Échange de token refusé. HR-Skills ne reconnaît pas ces clés.'],
            $echecs,
            ['Vérifier dans l\'ordre : (1) clé A et clé B ne sont pas interverties — '
                .'la clé A est celle du « Bearer », la clé B le secret ; (2) les clés sont bien '
                .'celles de ce compte marchand ; (3) elles sont actives dans le tableau de bord.'],
        );
    }
}
