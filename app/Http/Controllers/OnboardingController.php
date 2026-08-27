<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Les trois écrans d'accueil. Ils ne racontent pas la plateforme : ils
     * répondent aux trois questions qu'un nouveau venu se pose — comment je
     * trouve, à qui j'ai affaire, et qui paie quoi.
     *
     * @return array<int, array{titre: string, texte: string, icone: string}>
     */
    public static function etapes(): array
    {
        return [
            [
                'titre' => 'Trouvez le bon pro, près de chez vous',
                'texte' => 'Plombiers, électriciens, coiffeuses, répétiteurs : décrivez votre besoin en une phrase, la catégorie et le quartier sont reconnus tout seuls.',
                'icone' => 'search',
            ],
            [
                'titre' => 'Des prestataires vérifiés',
                'texte' => "La pièce d'identité des prestataires vérifiés a été contrôlée par notre équipe. Les avis viennent de clients dont la prestation est terminée.",
                'icone' => 'verified',
            ],
            [
                'titre' => 'Aucune commission, aucun paiement ici',
                'texte' => "SmartLink met en relation et s'efface : le règlement se convient directement avec le prestataire, hors plateforme. C'est gratuit pour les clients.",
                'icone' => 'handshake',
            ],
        ];
    }

    public function show(Request $request, int $etape = 1): View|RedirectResponse
    {
        $etapes = self::etapes();

        // Une étape hors bornes ramène à la première plutôt que d'échouer :
        // une URL tapée à la main ne doit pas donner une page d'erreur.
        if ($etape < 1 || $etape > count($etapes)) {
            return redirect()->route('onboarding.show', 1);
        }

        return view('onboarding.show', [
            'etape' => $etape,
            'total' => count($etapes),
            'contenu' => $etapes[$etape - 1],
        ]);
    }

    /**
     * Terminer ou passer mènent au même endroit : l'accueil se voit une fois.
     * Le forcer à qui l'a écarté serait le punir de son choix.
     */
    public function finish(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['onboarded_at' => now()])->save();

        return redirect()->route('dashboard');
    }
}
