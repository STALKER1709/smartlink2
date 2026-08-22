<?php

namespace App\Services\Ai;

/**
 * Ce qu'on a compris d'une phrase libre : « J'ai une fuite sous l'évier à
 * Bonamoussadi » devient une catégorie, une ville, un quartier et une urgence.
 */
class SearchIntent
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $categoryName = null,
        public readonly ?string $city = null,
        public readonly ?string $quarter = null,
        public readonly ?string $keywords = null,
        public readonly bool $urgent = false,
    ) {}

    public function isEmpty(): bool
    {
        return $this->categoryId === null
            && $this->city === null
            && $this->quarter === null
            && ($this->keywords === null || $this->keywords === '')
            && ! $this->urgent;
    }

    /**
     * Traduit l'intention en paramètres de recherche classiques. Le résultat
     * alimente une redirection : l'URL reste partageable, les filtres restent
     * visibles et modifiables, et un rafraîchissement ne refacture rien.
     *
     * @return array<string, string>
     */
    public function toQueryParameters(): array
    {
        return array_filter([
            'category_id' => $this->categoryId !== null ? (string) $this->categoryId : null,
            'city' => $this->city,
            'quarter' => $this->quarter,
            'term' => $this->keywords,
            'available_only' => $this->urgent ? '1' : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Résumé lisible de ce qui a été compris, montré à l'utilisateur pour
     * qu'il puisse corriger plutôt que subir.
     *
     * @return array<int, string>
     */
    public function summary(): array
    {
        return array_values(array_filter([
            $this->categoryName,
            $this->city,
            $this->quarter,
            $this->keywords,
            $this->urgent ? __('ui.search.understood_urgent') : null,
        ]));
    }
}
