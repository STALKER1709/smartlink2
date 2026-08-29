<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * L'e-mail de réinitialisation, seul message que SmartLink envoie par e-mail
 * et seule voie de récupération d'un compte.
 *
 * Il part alors que la personne n'est pas connectée : la langue de la session
 * n'existe pas encore. Sans le contrat `HasLocalePreference` sur le modèle,
 * il sortait donc toujours dans la langue par défaut, quelle que soit celle
 * choisie par le destinataire.
 */
class PasswordResetLocaleTest extends TestCase
{
    use RefreshDatabase;

    private function messagePour(string $locale): Email
    {
        $user = User::factory()->client()->create(['locale' => $locale]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();

        $envois = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $envois, 'Un e-mail doit partir.');

        return $envois[0]->getOriginalMessage();
    }

    public function test_a_french_speaking_recipient_gets_a_fully_french_email(): void
    {
        $message = $this->messagePour('fr');
        $corps = $message->getTextBody();

        $this->assertStringContainsString('Réinitialisation', $message->getSubject());
        $this->assertStringContainsString('Bonjour', $corps);
        $this->assertStringContainsString('Ce lien expire dans', $corps);
        $this->assertStringContainsString('À bientôt', $corps);

        /*
         * Le point qui a motivé ce test : seul le libellé du bouton était
         * traduit. Le corps entier — l'explication, le délai, la formule de
         * politesse, le pied de page — partait en anglais à un public
         * francophone, sur le message qui sert précisément à récupérer un
         * compte perdu. Un e-mail à moitié traduit se prend pour du
         * hameçonnage, et c'est celui-là qu'il ne faut pas voir ignoré.
         */
        foreach (['You are receiving this email', 'Regards,', 'All rights reserved', 'Hello!'] as $anglais) {
            $this->assertStringNotContainsString($anglais, $corps, "« {$anglais} » ne doit pas rester en anglais.");
        }
    }

    public function test_an_english_speaking_recipient_keeps_english(): void
    {
        $message = $this->messagePour('en');

        $this->assertStringContainsString('Reset your password', $message->getSubject());
        $this->assertStringContainsString('You are receiving this email', $message->getTextBody());
    }
}
