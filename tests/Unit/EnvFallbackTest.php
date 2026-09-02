<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * `env('X', 30)` ne rend 30 que si X n'existe pas. Si X existe et vaut la
 * chaîne vide — ce qui arrive dès qu'on colle un bloc .env sans remplir toutes
 * les lignes — on récupère '' et la valeur par défaut est perdue. Sur un
 * réglage numérique, `(int) ''` vaut zéro : un abonnement prolongé de zéro
 * jour, sans la moindre erreur. C'est arrivé en production.
 */
class EnvFallbackTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['SMARTLINK_ABSENTE', 'SMARTLINK_VIDE', 'SMARTLINK_PLEINE'] as $cle) {
            unset($_ENV[$cle], $_SERVER[$cle]);
            putenv($cle);
        }

        parent::tearDown();
    }

    private function poser(string $cle, string $valeur): void
    {
        $_ENV[$cle] = $_SERVER[$cle] = $valeur;
        putenv("{$cle}={$valeur}");
    }

    public function test_an_absent_variable_falls_back(): void
    {
        $this->assertSame(30, env_or('SMARTLINK_ABSENTE', 30));
    }

    public function test_an_empty_variable_falls_back_too(): void
    {
        $this->poser('SMARTLINK_VIDE', '');

        $this->assertSame(30, env_or('SMARTLINK_VIDE', 30),
            "Une variable vide doit se comporter comme absente : c'est tout l'intérêt de ce helper.");
    }

    public function test_a_filled_variable_wins(): void
    {
        $this->poser('SMARTLINK_PLEINE', '7');

        $this->assertSame('7', env_or('SMARTLINK_PLEINE', 30));
    }

    public function test_every_critical_setting_survives_an_empty_value(): void
    {
        // Les réglages dont une valeur nulle coûte de l'argent ou casse un appel.
        foreach ([
            'subscription.cycle_days' => 30,
            'subscription.trial_days' => 30,
            'ai.limits.daily_messages_per_user' => 20,
        ] as $cle => $attendu) {
            $this->assertSame($attendu, config($cle), "config({$cle}) doit garder sa valeur par défaut.");
        }

        $this->assertStringStartsWith('http', (string) config('payment.hrskills.base_url'));
    }
}
