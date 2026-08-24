<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\Service;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchServices(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Service::query()
            ->active()
            ->fromListedProvider()
            ->with(['provider.providerProfile', 'category', 'images']);

        if (! empty($filters['category_id'])) {
            $query->inCategory((int) $filters['category_id']);
        }

        if (! empty($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (! empty($filters['quarter'])) {
            $query->whereLike('quarter', '%'.$filters['quarter'].'%', caseSensitive: false);
        }

        if (! empty($filters['term'])) {
            $query->searchTerm($filters['term']);
        }

        if (! empty($filters['available_only'])) {
            $query->available();
        }

        // Les prestataires du palier Pro remontent, à critère de tri égal.
        $query->orderByDesc(
            ProviderProfile::query()
                ->select('is_promoted')
                ->whereColumn('provider_profiles.user_id', 'services.provider_id')
                ->limit(1)
        );

        match ($filters['sort'] ?? null) {
            'price_asc' => $query->orderBy('price_amount'),
            'price_desc' => $query->orderByDesc('price_amount'),
            default => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchProviders(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = ProviderProfile::query()->listed()->with(['user', 'category']);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['city'])) {
            $query->inCity($filters['city']);
        }

        if (! empty($filters['verified_only'])) {
            $query->verified();
        }

        if (! empty($filters['term'])) {
            $term = $filters['term'];
            $query->where(function ($q) use ($term) {
                $q->whereLike('business_name', "%{$term}%", caseSensitive: false)
                    ->orWhereLike('description', "%{$term}%", caseSensitive: false);
            });
        }

        return $query
            ->orderByDesc('is_promoted')
            ->orderByDesc('rating_avg')
            ->paginate($perPage)
            ->withQueryString();
    }
}
