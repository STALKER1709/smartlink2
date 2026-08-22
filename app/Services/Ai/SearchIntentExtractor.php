<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Models\AiUsage;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Traduit une phrase libre en filtres de recherche. Tout échec renvoie null :
 * l'appelant retombe alors sur la recherche par mot-clé, sans rien signaler.
 */
class SearchIntentExtractor
{
    private const SENTINEL = '';

    public function __construct(
        private readonly Client $client,
        private readonly AiGate $gate,
        private readonly AiUsageRecorder $usage,
    ) {}

    public function extract(string $query, ?User $user = null): ?SearchIntent
    {
        $query = trim($query);

        if ($query === '' || ! $this->gate->allows($user, AiUsage::FEATURE_SEARCH)) {
            return null;
        }

        $categories = $this->categories();
        $cities = $this->cities();

        if ($categories === []) {
            return null;
        }

        try {
            $extracted = $this->ask($query, $categories, $cities, $user);
        } catch (\Throwable $e) {
            Log::error('[IA] Extraction de recherche en échec, repli sur le mot-clé', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($extracted === null) {
            return null;
        }

        return $this->toIntent($extracted);
    }

    /**
     * @param  array<string, int>  $categories
     * @param  array<int, string>  $cities
     * @return array<string, mixed>|null
     */
    private function ask(string $query, array $categories, array $cities, ?User $user): ?array
    {
        $model = config('ai.models.search');

        $response = $this->client->messages->create(
            model: $model,
            maxTokens: 512,
            system: $this->instructions(),
            messages: [['role' => 'user', 'content' => $query]],
            outputConfig: [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'enum' => [...array_keys($categories), self::SENTINEL],
                                'description' => 'Catégorie de service demandée, ou chaîne vide si indéterminable.',
                            ],
                            'city' => [
                                'type' => 'string',
                                'enum' => [...$cities, self::SENTINEL],
                                'description' => 'Ville mentionnée, ou chaîne vide.',
                            ],
                            'quarter' => [
                                'type' => 'string',
                                'description' => 'Quartier mentionné, ou chaîne vide.',
                            ],
                            'keywords' => [
                                'type' => 'string',
                                'description' => 'Deux ou trois mots décrivant le besoin, hors ville et quartier. Chaîne vide si rien à ajouter.',
                            ],
                            'urgent' => [
                                'type' => 'boolean',
                                'description' => 'Vrai si la personne exprime une urgence ou un besoin immédiat.',
                            ],
                        ],
                        'required' => ['category', 'city', 'quarter', 'keywords', 'urgent'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );

        $this->usage->record(
            user: $user,
            feature: AiUsage::FEATURE_SEARCH,
            model: $model,
            inputTokens: $response->usage->inputTokens,
            outputTokens: $response->usage->outputTokens,
        );

        foreach ($response->content as $block) {
            if (($block->type ?? null) === 'text') {
                $decoded = json_decode($block->text, true);

                return is_array($decoded) ? $decoded : null;
            }
        }

        return null;
    }

    private function instructions(): string
    {
        return <<<'TXT'
        Tu traduis la demande d'un utilisateur de SmartLink — une plateforme
        camerounaise de mise en relation avec des prestataires de services — en
        filtres de recherche.

        N'extrais que ce que la phrase dit réellement. N'invente ni ville, ni
        quartier, ni catégorie : dans le doute, renvoie une chaîne vide. Choisis
        la catégorie la plus proche du besoin exprimé, pas du mot employé : une
        fuite d'eau relève de la plomberie, une panne de courant de l'électricité.

        Le champ « keywords » ne sert qu'à préciser le besoin en deux ou trois
        mots. N'y remets ni la ville, ni le quartier, ni le nom de la catégorie.
        TXT;
    }

    /**
     * Convertit la sortie du modèle en intention exploitable. Public parce
     * que c'est la barrière de confiance : ce que le modèle renvoie n'entre
     * dans l'application qu'après être passé par ici.
     *
     * @param  array<string, mixed>  $extracted
     */
    public function toIntent(array $extracted): SearchIntent
    {
        $categories = $this->categories();

        // Le schéma contraint déjà les valeurs, mais rien n'oblige à faire
        // confiance : chaque champ est revérifié contre les données réelles.
        $categoryName = $this->clean($extracted['category'] ?? null);
        $categoryId = $categoryName !== null ? ($categories[$categoryName] ?? null) : null;

        return new SearchIntent(
            categoryId: $categoryId,
            categoryName: $categoryId !== null ? $categoryName : null,
            city: $this->clean($extracted['city'] ?? null),
            quarter: $this->clean($extracted['quarter'] ?? null, 60),
            keywords: $this->clean($extracted['keywords'] ?? null, 80),
            urgent: (bool) ($extracted['urgent'] ?? false),
        );
    }

    private function clean(mixed $value, int $maxLength = 120): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }

    /**
     * @return array<string, int> nom de catégorie => identifiant
     */
    private function categories(): array
    {
        return ServiceCategory::query()
            ->active()
            ->orderBy('name')
            ->pluck('id', 'name')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function cities(): array
    {
        return ProviderProfile::query()
            ->listed()
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }
}
