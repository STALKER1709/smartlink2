<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La bascule FR/EN est affichée dans la barre de navigation : elle promet une
 * interface anglaise. La refonte avait figé le texte des vues en français, si
 * bien que passer en anglais ne changeait presque rien — et il n'existait
 * aucun `lang/en.json` pour traduire les chaînes que les vues passent bien à
 * `__()`, puisque leur clé *est* le français.
 *
 * Ces tests couvrent la coquille publique : la barre de navigation, l'accueil
 * et le pied de page, c'est-à-dire ce qu'un visiteur voit avant tout le reste.
 */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function accueil(string $locale): string
    {
        // Les sections « Catégories populaires » et « Derniers services » ne
        // s'affichent qu'avec du contenu : sans elles, le test passerait sans
        // rien avoir regardé.
        $provider = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $provider->id, 'is_listed' => true]);
        Service::factory()->create([
            'provider_id' => $provider->id,
            'category_id' => ServiceCategory::factory()->create()->id,
            'status' => Service::STATUS_ACTIVE,
        ]);

        $this->get(route('locale.switch', $locale))->assertRedirect();

        return $this->get(route('home'))->assertOk()->getContent();
    }

    public function test_the_public_shell_is_translated_in_english(): void
    {
        $page = $this->accueil('en');

        foreach (['Providers', 'Log in', 'Sign up', 'Popular categories',
            'How it works', 'Free for clients', 'No commission'] as $attendu) {
            $this->assertStringContainsString($attendu, $page, "« {$attendu} » manque à la version anglaise.");
        }
    }

    /**
     * Le vrai symptôme d'un `lang/en.json` incomplet : la page reste à moitié
     * française sans que rien n'échoue.
     */
    public function test_no_french_shell_text_survives_in_english(): void
    {
        $page = $this->accueil('en');

        foreach (['Prestataires', 'Se connecter', "S'inscrire", 'Catégories populaires',
            'Comment ça marche', 'Gratuit pour les clients', 'Aucune commission',
            'Mentions légales', 'Conditions générales'] as $francais) {
            $this->assertStringNotContainsString($francais, $page, "« {$francais} » reste en français.");
        }
    }

    /**
     * L'autre moitié du contrat : le français ne doit pas avoir bougé. Le
     * public de SmartLink est francophone — une régression ici coûterait bien
     * plus cher que l'anglais n'apporte.
     */
    public function test_french_is_untouched(): void
    {
        $page = $this->accueil('fr');

        foreach (['Prestataires', 'Se connecter', 'Catégories populaires',
            'Comment ça marche', 'Aucune commission'] as $attendu) {
            $this->assertStringContainsString($attendu, $page, "« {$attendu} » manque à la version française.");
        }
    }

    /**
     * Les libellés de statut d'une demande passent par RequestStatus, seul
     * endroit où ils sont écrits — ils étaient en français en dur.
     */
    public function test_request_statuses_follow_the_locale(): void
    {
        app()->setLocale('en');
        $this->assertSame('Accepted', RequestStatus::label('accepted'));

        app()->setLocale('fr');
        $this->assertSame('Acceptée', RequestStatus::label('accepted'));
    }
}
