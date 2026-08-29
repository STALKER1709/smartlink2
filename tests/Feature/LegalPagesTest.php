<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Les pages légales doivent être lisibles avant toute inscription, et dire la
 * vérité sur ce que la plateforme fait des données. Ce qui est gardé ici :
 * l'accessibilité publique, l'aveu de non-validation tant qu'un juriste n'est
 * pas passé, et la cohérence avec le modèle économique.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'conditions' => ['legal.terms'],
            'mentions' => ['legal.notice'],
            'confidentialité' => ['legal.privacy'],
        ];
    }

    #[DataProvider('pages')]
    public function test_a_guest_can_read_it(string $route): void
    {
        $this->get(route($route))->assertOk();
    }

    #[DataProvider('pages')]
    public function test_it_admits_it_has_not_been_reviewed_by_a_lawyer(string $route): void
    {
        config()->set('legal.valide_juridiquement', false);

        $this->get(route($route))->assertSee('non validé juridiquement');
    }

    #[DataProvider('pages')]
    public function test_the_warning_lifts_once_the_review_is_declared(string $route): void
    {
        config()->set('legal.valide_juridiquement', true);

        $this->get(route($route))->assertDontSee('non validé juridiquement');
    }

    /**
     * Des mentions légales vides qui auraient l'air complètes valent moins que
     * pas de mentions du tout : l'absence doit se voir.
     */
    public function test_missing_publisher_identity_is_announced_rather_than_hidden(): void
    {
        config()->set('legal.editeur', array_fill_keys(array_keys(config('legal.editeur')), null));

        $this->get(route('legal.notice'))->assertSee('Identité de l\'éditeur non renseignée');
    }

    public function test_a_filled_identity_replaces_the_warning(): void
    {
        $this->identiteComplete();
        config()->set('legal.editeur.raison_sociale', 'SmartLink SARL');

        $this->get(route('legal.notice'))
            ->assertSee('SmartLink SARL')
            ->assertDontSee('Identité de l\'éditeur non renseignée');
    }

    /**
     * Le bandeau ne se déclenchait que si *toutes* les mentions manquaient :
     * renseigner la raison sociale le faisait disparaître alors que le RCCM ou
     * le siège restaient vides — c'est-à-dire au moment précis où on croit
     * avoir terminé, et où plus rien ne signale ce qu'il reste à faire.
     */
    public function test_a_single_missing_detail_keeps_the_warning(): void
    {
        $this->identiteComplete();
        config()->set('legal.editeur.siege', '');

        $this->get(route('legal.notice'))
            ->assertSee('Identité de l\'éditeur non renseignée');
    }

    /**
     * Toutes les clés que la vue lit, renseignées. config/legal.php les
     * fournit toujours toutes : seule leur valeur peut être vide.
     */
    private function identiteComplete(): void
    {
        $cles = array_keys((array) config('legal.editeur'));

        config()->set('legal.editeur', array_combine(
            $cles,
            array_map(fn (string $cle) => 'valeur '.$cle, $cles),
        ));
    }

    /**
     * Le point qui distingue SmartLink d'une place de marché doit figurer dans
     * les conditions, pas seulement sur la page d'accueil.
     */
    public function test_the_terms_state_that_no_money_passes_through_the_platform(): void
    {
        $this->get(route('legal.terms'))
            ->assertSee('Aucune somme ne transite entre un client et un prestataire')
            ->assertSee('indicatifs');
    }

    /**
     * Un utilisateur doit pouvoir savoir que son texte part chez un tiers, et
     * lequel. C'est le genre de fait qu'on n'écrit que si on a lu le code.
     */
    public function test_the_privacy_policy_names_the_ai_processor(): void
    {
        $this->get(route('legal.privacy'))
            ->assertSee('Anthropic')
            ->assertSee('Ne lui sont', false)
            ->assertSee('signale seulement');
    }

    public function test_the_privacy_policy_lists_every_processor_from_the_config(): void
    {
        $reponse = $this->get(route('legal.privacy'))->assertOk();

        foreach (config('legal.sous_traitants') as $tiers) {
            $reponse->assertSee($tiers['nom']);
        }
    }

    /**
     * Écrites mais introuvables, elles n'existeraient pas.
     */
    public function test_they_are_reachable_from_the_footer(): void
    {
        $this->get(route('home'))
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'))
            ->assertSee(route('legal.notice'));
    }

    /**
     * On ne s'engage pas à des conditions qu'on n'a pas pu lire.
     */
    public function test_the_registration_form_links_to_them(): void
    {
        $this->get(route('register'))
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'));
    }
}
