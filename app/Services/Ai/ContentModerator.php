<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Models\AiUsage;
use App\Models\ModerationReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Pré-filtre les contenus publiés. L'IA classe et signale, jamais elle ne
 * supprime : un administrateur garde la décision. Un contenu qu'elle n'a pas
 * pu examiner reste simplement en ligne, comme avant.
 */
class ContentModerator
{
    /** Catégories que le modèle a le droit de retenir. */
    private const CATEGORIES = [
        'contact_hors_plateforme',
        'arnaque_probable',
        'propos_haineux',
        'contenu_sexuel',
        'hors_sujet',
        'coordonnees_bancaires',
    ];

    public function __construct(
        private readonly Client $client,
        private readonly AiGate $gate,
        private readonly AiUsageRecorder $usage,
    ) {}

    public function review(Model $content, string $text): ?ModerationReport
    {
        $text = trim($text);

        // La modération tourne en file d'attente, sans utilisateur connecté :
        // seuls le pilote, la clé et le plafond mensuel la bornent.
        if ($text === '' || ! $this->gate->allows(null, AiUsage::FEATURE_MODERATION)) {
            return null;
        }

        try {
            $verdict = $this->ask($text);
        } catch (\Throwable $e) {
            Log::error('[IA] Modération en échec', [
                'error' => $e->getMessage(),
                'content' => $content::class.'#'.$content->getKey(),
            ]);

            return null;
        }

        if ($verdict === null) {
            return null;
        }

        return ModerationReport::updateOrCreate(
            [
                'moderatable_type' => $content->getMorphClass(),
                'moderatable_id' => $content->getKey(),
            ],
            $verdict + ['reviewed_by' => null, 'reviewed_at' => null],
        );
    }

    /**
     * @return array{verdict: string, categories: array<int, string>, reason: ?string, model: string}|null
     */
    private function ask(string $text): ?array
    {
        $model = config('ai.models.moderation');

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: 512,
            system: $this->instructions(),
            messages: [['role' => 'user', 'content' => mb_substr($text, 0, 4000)]],
            outputConfig: [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'flagged' => [
                                'type' => 'boolean',
                                'description' => 'Vrai si le contenu mérite le regard d\'un administrateur.',
                            ],
                            'categories' => [
                                'type' => 'array',
                                'items' => ['type' => 'string', 'enum' => self::CATEGORIES],
                                'description' => 'Motifs retenus, vide si le contenu est sain.',
                            ],
                            'reason' => [
                                'type' => 'string',
                                'description' => 'Une phrase expliquant le signalement, vide si le contenu est sain.',
                            ],
                        ],
                        'required' => ['flagged', 'categories', 'reason'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        $this->usage->record(
            user: null,
            feature: AiUsage::FEATURE_MODERATION,
            model: $model,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
        );

        foreach ($response->content as $block) {
            if (($block->type ?? null) === 'text') {
                return $this->toVerdict(json_decode($block->text, true), $model);
            }
        }

        return null;
    }

    private function instructions(): string
    {
        return <<<'TXT'
        Tu examines un contenu publié sur SmartLink, une plateforme camerounaise
        de mise en relation avec des prestataires de services : une annonce de
        service, ou l'avis laissé par un client.

        Signale uniquement ce qui mérite qu'un administrateur y jette un œil :

        - contact_hors_plateforme : le texte pousse à traiter en dehors du site,
          ou communique un numéro pour court-circuiter la messagerie interne
        - arnaque_probable : demande d'avance suspecte, promesse invraisemblable
        - propos_haineux : insultes, menaces, propos discriminatoires
        - contenu_sexuel : contenu à caractère sexuel
        - coordonnees_bancaires : numéro de compte, code de retrait, identifiants
        - hors_sujet : le texte n'a rien à voir avec une prestation de service

        Ne signale pas un texte simplement parce qu'il est mal écrit, très court,
        en pidgin ou mêlant français et anglais : beaucoup de prestataires écrivent
        ainsi, et ce n'est pas un problème. Un avis négatif mais argumenté n'est pas
        non plus à signaler.

        Dans le doute, ne signale pas. Un faux signalement coûte du temps
        d'administration et pénalise un prestataire honnête.
        TXT;
    }

    /**
     * @return array{verdict: string, categories: array<int, string>, reason: ?string, model: string}|null
     */
    private function toVerdict(mixed $decoded, string $model): ?array
    {
        if (! is_array($decoded)) {
            return null;
        }

        // Seules les catégories connues sont retenues : le schéma les contraint
        // déjà, mais rien n'oblige à s'en remettre à lui.
        $categories = array_values(array_intersect(
            is_array($decoded['categories'] ?? null) ? $decoded['categories'] : [],
            self::CATEGORIES,
        ));

        $flagged = ($decoded['flagged'] ?? false) === true && $categories !== [];
        $reason = is_string($decoded['reason'] ?? null) ? trim($decoded['reason']) : '';

        return [
            'verdict' => $flagged ? ModerationReport::VERDICT_FLAGGED : ModerationReport::VERDICT_CLEAN,
            'categories' => $categories,
            'reason' => $flagged && $reason !== '' ? mb_substr($reason, 0, 500) : null,
            'model' => $model,
        ];
    }
}
