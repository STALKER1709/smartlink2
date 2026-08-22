<?php

namespace App\Console\Commands;

use App\Services\Payment\HrSkills\HrSkillsCore;
use Illuminate\Console\Command;

/**
 * Vérifie la configuration d'encaissement avant qu'un vrai paiement ne la
 * mette à l'épreuve. À lancer après chaque déploiement.
 */
class CheckPaymentConfiguration extends Command
{
    protected $signature = 'payment:check';

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
                $this->info("Clés cohérentes, environnement « {$envA} ».");
                $this->line('Racine des appels : '.HrSkillsCore::apiRoot(
                    (string) config('payment.hrskills.base_url'),
                    $keyA,
                ));
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
}
