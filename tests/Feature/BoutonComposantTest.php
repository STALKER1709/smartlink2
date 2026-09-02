<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Le bouton primaire rend la balise que sa destination commande.
 *
 * Dix-sept boutons primaires du dépôt étaient des liens. Le composant ne
 * rendant qu'un `<button>`, chacun avait été réécrit à la main — et chacun
 * avait dérivé de son voisin.
 */
class BoutonComposantTest extends TestCase
{
    public function test_without_a_destination_it_stays_a_submit_button(): void
    {
        $rendu = Blade::render('<x-primary-button>Envoyer</x-primary-button>');

        $this->assertStringContainsString('<button', $rendu);
        $this->assertStringContainsString('type="submit"', $rendu);
        $this->assertStringNotContainsString('<a ', $rendu);
    }

    /**
     * Un lien doit rester un `<a>` : c'est ce que le clavier et le lecteur
     * d'écran attendent, et ce qu'un clic du milieu ouvre dans un onglet.
     */
    public function test_with_a_destination_it_becomes_a_link(): void
    {
        $rendu = Blade::render('<x-primary-button href="/services">Voir</x-primary-button>');

        $this->assertStringContainsString('<a href="/services"', $rendu);
        $this->assertStringNotContainsString('<button', $rendu);
    }

    /**
     * Les deux balises portent exactement la même mise en forme : c'est tout
     * l'intérêt, et c'est ce qui se défait si on l'écrit deux fois.
     */
    public function test_both_shapes_carry_the_same_styling(): void
    {
        foreach (['rounded-full', 'bg-primary', 'font-button-text', 'px-6', 'py-2.5'] as $classe) {
            $this->assertStringContainsString($classe, Blade::render('<x-primary-button>A</x-primary-button>'));
            $this->assertStringContainsString($classe, Blade::render('<x-primary-button href="/x">A</x-primary-button>'));
        }
    }

    public function test_the_secondary_button_follows_the_same_rule(): void
    {
        $this->assertStringContainsString('<a href="/x"', Blade::render('<x-secondary-button href="/x">A</x-secondary-button>'));
        $this->assertStringContainsString('<button', Blade::render('<x-secondary-button>A</x-secondary-button>'));
    }
}
