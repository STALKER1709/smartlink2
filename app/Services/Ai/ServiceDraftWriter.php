<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Models\AiUsage;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Rédige un titre et une description de service à partir de quelques mots.
 * Beaucoup de prestataires sont plus à l'aise à l'oral qu'à l'écrit : c'est
 * un frein réel à la publication, pas un ornement.
 */
class ServiceDraftWriter
{
    public function __construct(
        private readonly Client $client,
        private readonly AiGate $gate,
        private readonly AiUsageRecorder $usage,
    ) {}

    /**
     * @return array{title: string, description: string}|null
     */
    public function draft(User $provider, string $notes, ?ServiceCategory $category, ?string $city): ?array
    {
        $notes = trim($notes);

        if ($notes === '' || ! $this->gate->allows($provider, AiUsage::FEATURE_WRITING)) {
            return null;
        }

        if (! $this->isOpenToPlan($provider)) {
            return null;
        }

        try {
            return $this->ask($provider, $notes, $category, $city);
        } catch (\Throwable $e) {
            Log::error('[IA] Rédaction assistée en échec', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * La rédaction assistée est un avantage de palier : elle n'est ouverte
     * qu'aux prestataires dont l'abonnement en cours la comprend.
     */
    public function isOpenToPlan(User $provider): bool
    {
        return $provider->currentPlan()?->has_ai_writing === true;
    }

    /**
     * Proposer le bouton à qui ne peut pas s'en servir n'aide personne : la
     * vue interroge cette méthode avant d'afficher quoi que ce soit.
     */
    public function isAvailableFor(User $provider): bool
    {
        return $this->isOpenToPlan($provider)
            && $this->gate->allows($provider, AiUsage::FEATURE_WRITING);
    }

    /**
     * @return array{title: string, description: string}|null
     */
    private function ask(User $provider, string $notes, ?ServiceCategory $category, ?string $city): ?array
    {
        $model = config('ai.models.writing');

        $context = collect([
            $category !== null ? "Catégorie : {$category->name}" : null,
            $city !== null && $city !== '' ? "Ville : {$city}" : null,
            "Ce que le prestataire a écrit : {$notes}",
        ])->filter()->implode("\n");

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: 1024,
            system: $this->instructions(),
            messages: [['role' => 'user', 'content' => $context]],
            outputConfig: [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Titre du service, 60 caractères au maximum.',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Description du service, entre 300 et 800 caractères.',
                            ],
                        ],
                        'required' => ['title', 'description'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        $this->usage->record(
            user: $provider,
            feature: AiUsage::FEATURE_WRITING,
            model: $model,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
        );

        foreach ($response->content as $block) {
            if (($block->type ?? null) === 'text') {
                return $this->clean(json_decode($block->text, true));
            }
        }

        return null;
    }

    private function instructions(): string
    {
        return <<<'TXT'
        Tu rédiges l'annonce d'un prestataire de services au Cameroun, pour la
        plateforme SmartLink, à partir de ses propres mots.

        Écris à la première personne, dans un français simple et concret, comme
        l'artisan le dirait lui-même. Décris ce qu'il fait, comment il travaille et
        ce que le client peut attendre.

        N'invente aucun tarif, aucun délai, aucune certification, aucune année
        d'expérience et aucune zone d'intervention qui ne soit pas dans ses mots.
        Si ses notes sont maigres, reste général plutôt que d'inventer.

        Ne promets rien au nom de SmartLink et ne parle jamais de paiement en
        ligne : la plateforme ne prélève rien sur les prestations.
        TXT;
    }

    /**
     * @return array{title: string, description: string}|null
     */
    private function clean(mixed $decoded): ?array
    {
        if (! is_array($decoded)) {
            return null;
        }

        $title = is_string($decoded['title'] ?? null) ? trim($decoded['title']) : '';
        $description = is_string($decoded['description'] ?? null) ? trim($decoded['description']) : '';

        if ($title === '' || $description === '') {
            return null;
        }

        return [
            // Les mêmes bornes que la validation du formulaire : une proposition
            // que le formulaire refuserait ne rend service à personne.
            'title' => mb_substr($title, 0, 255),
            'description' => mb_substr($description, 0, 3000),
        ];
    }
}
