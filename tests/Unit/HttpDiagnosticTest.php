<?php

namespace Tests\Unit;

use App\Support\HttpDiagnostic;
use PHPUnit\Framework\TestCase;

/**
 * `ConnectionException` recouvre quatre pannes qui ne se corrigent pas de la
 * même façon. Les afficher sous ce seul nom, c'est dire à quelqu'un que ça ne
 * marche pas sans lui dire quoi faire — et c'est ce que faisaient les deux
 * commandes d'images.
 */
class HttpDiagnosticTest extends TestCase
{
    public function test_a_missing_certificate_store_is_named_and_its_remedy_given(): void
    {
        // Le cas de loin le plus fréquent : PHP sous Windows, livré sans
        // magasin de certificats racine, échoue sur *toute* requête HTTPS —
        // alors que le navigateur et Git, qui ont le leur, passent.
        $conseil = HttpDiagnostic::conseil(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate'
        );

        $this->assertNotNull($conseil);
        $this->assertStringContainsString('cacert.pem', $conseil);
        $this->assertStringContainsString('curl.cainfo', $conseil);
        $this->assertStringContainsString('Ne désactivez pas la vérification TLS', $conseil);
    }

    public function test_each_failure_gets_its_own_advice(): void
    {
        $cas = [
            'cURL error 56: CONNECT tunnel failed, response 403' => 'proxy',
            'cURL error 6: Could not resolve host: commons.wikimedia.org' => 'DNS',
            'cURL error 7: Failed to connect: Connection refused' => 'refusée',
            'cURL error 28: Operation timed out after 20000 milliseconds' => 'Délai',
        ];

        foreach ($cas as $message => $attendu) {
            $conseil = HttpDiagnostic::conseil($message);

            $this->assertNotNull($conseil, 'Aucun conseil pour : '.$message);
            $this->assertStringContainsString($attendu, $conseil);
        }
    }

    public function test_an_unknown_failure_invents_no_advice(): void
    {
        $this->assertNull(HttpDiagnostic::conseil('Quelque chose d\'inattendu s\'est produit'));
    }

    public function test_the_summary_keeps_the_cause_on_one_line(): void
    {
        $resume = HttpDiagnostic::resume(new \RuntimeException("Ligne une\n   ligne deux"));
        $this->assertSame('Ligne une ligne deux', $resume);

        $long = HttpDiagnostic::resume(new \RuntimeException(str_repeat('a', 300)), 50);
        $this->assertSame(51, mb_strlen($long), 'Cinquante caractères, plus les points de suite.');

        // Une exception sans message ne doit pas rendre une ligne vide : le nom
        // de la classe reste la seule chose à dire.
        $this->assertSame('RuntimeException', HttpDiagnostic::resume(new \RuntimeException('')));
    }
}
