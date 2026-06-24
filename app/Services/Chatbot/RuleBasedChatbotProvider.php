<?php

namespace App\Services\Chatbot;

use App\Contracts\ChatbotProvider;
use Illuminate\Support\Str;

/**
 * Default chatbot implementation: simple French keyword matching.
 * Swap the App\Contracts\ChatbotProvider binding in AppServiceProvider
 * to plug in a real external AI API later without touching ChatbotService.
 */
class RuleBasedChatbotProvider implements ChatbotProvider
{
    /**
     * @var array<int, array{keywords: array<int, string>, reply: string}>
     */
    private array $rules = [
        [
            'keywords' => ['bonjour', 'salut', 'bonsoir'],
            'reply' => "Bonjour ! Je suis l'assistant SmartLink. Je peux vous aider à trouver un prestataire, "
                .'envoyer une demande, ou suivre son statut. Que souhaitez-vous savoir ?',
        ],
        [
            'keywords' => ['trouver', 'rechercher', 'chercher', 'prestataire', 'service'],
            'reply' => 'Pour trouver un prestataire, utilisez la barre de recherche : vous pouvez filtrer par '
                .'catégorie, par ville et par mot-clé.',
        ],
        [
            'keywords' => ['demande', 'envoyer', 'contacter'],
            'reply' => 'Pour contacter un prestataire, ouvrez sa fiche puis cliquez sur "Envoyer une demande". '
                .'Vous recevrez une notification dès qu\'il répond.',
        ],
        [
            'keywords' => ['statut', 'suivi', 'avancement'],
            'reply' => 'Vous pouvez suivre le statut de vos demandes (envoyée, vue, acceptée, refusée, en cours, '
                .'terminée) depuis votre tableau de bord.',
        ],
        [
            'keywords' => ['paiement', 'payer', 'argent', 'prix', 'tarif'],
            'reply' => 'SmartLink ne gère aucun paiement en ligne. Les modalités financières sont à convenir '
                .'directement entre vous et le prestataire, hors plateforme.',
        ],
        [
            'keywords' => ['compte', 'inscription', 'inscrire', 'mot de passe'],
            'reply' => 'Vous pouvez créer un compte Client ou Prestataire depuis la page d\'inscription. En cas '
                .'d\'oubli de mot de passe, utilisez le lien "Mot de passe oublié" sur la page de connexion.',
        ],
        [
            'keywords' => ['merci'],
            'reply' => "Avec plaisir ! N'hésitez pas si vous avez d'autres questions.",
        ],
    ];

    public function respond(string $message, array $history = []): string
    {
        $normalized = Str::lower(Str::ascii($message));

        foreach ($this->rules as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    return $rule['reply'];
                }
            }
        }

        return "Je n'ai pas bien compris votre demande. Vous pouvez me parler de la recherche d'un prestataire, "
            ."de l'envoi d'une demande, ou du suivi de son statut. Pour toute autre question, contactez le support.";
    }
}
