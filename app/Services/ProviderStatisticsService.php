<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Statistiques de performance d'un prestataire, calculées à partir des
 * données déjà présentes : rien n'est pisté en plus pour les produire.
 */
class ProviderStatisticsService
{
    /** Statuts qui témoignent d'une demande acceptée, quelle que soit la suite. */
    private const ACCEPTED = [
        ServiceRequest::STATUS_ACCEPTED,
        ServiceRequest::STATUS_IN_PROGRESS,
        ServiceRequest::STATUS_COMPLETED,
    ];

    /**
     * @return array<string, mixed>
     */
    public function forProvider(User $provider, int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        $byStatus = ServiceRequest::query()
            ->where('provider_id', $provider->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $answered = $byStatus->only(array_merge(self::ACCEPTED, [ServiceRequest::STATUS_REFUSED]))->sum();
        $accepted = $byStatus->only(self::ACCEPTED)->sum();

        return [
            'period_days' => $days,
            'requests_total' => (int) $byStatus->sum(),
            'requests_recent' => ServiceRequest::query()
                ->where('provider_id', $provider->id)
                ->where('created_at', '>=', $since)
                ->count(),
            'by_status' => $byStatus,

            // Sur les demandes auxquelles il a effectivement répondu : une
            // demande jamais ouverte ne dit rien de sa façon de travailler.
            'acceptance_rate' => $answered > 0 ? (int) round($accepted / $answered * 100) : null,

            'completion_rate' => $accepted > 0
                ? (int) round(($byStatus[ServiceRequest::STATUS_COMPLETED] ?? 0) / $accepted * 100)
                : null,

            'median_response_hours' => $this->medianResponseHours($provider),

            'rating_avg' => (float) ($provider->providerProfile?->rating_avg ?? 0),
            'rating_count' => (int) ($provider->providerProfile?->rating_count ?? 0),
            'rating_breakdown' => $this->ratingBreakdown($provider),

            'top_services' => $this->topServices($provider),
            'services_active' => $provider->services()->active()->count(),

            // Les maquettes affichent une tendance à côté de chaque chiffre :
            // un pourcentage seul ne dit pas si l'on progresse. La comparaison
            // porte sur la période précédente de même durée.
            'trends' => $this->trends($provider, $days),
        ];
    }

    /**
     * Variation par rapport à la période précédente de même durée, en points
     * de pourcentage arrondis. `null` quand la période précédente est vide :
     * passer de zéro à trois n'est pas « +300 % », c'est un début.
     *
     * @return array<string, int|null>
     */
    private function trends(User $provider, int $days): array
    {
        $finPrecedente = now()->subDays($days)->startOfDay();
        $debutPrecedente = now()->subDays($days * 2)->startOfDay();

        $recu = fn ($depuis, $jusqua = null) => ServiceRequest::query()
            ->where('provider_id', $provider->id)
            ->where('created_at', '>=', $depuis)
            ->when($jusqua !== null, fn ($q) => $q->where('created_at', '<', $jusqua))
            ->count();

        $courant = $recu($finPrecedente);
        $precedent = $recu($debutPrecedente, $finPrecedente);

        return [
            'requests_recent' => $precedent > 0
                ? (int) round(($courant - $precedent) / $precedent * 100)
                : null,
        ];
    }

    /**
     * Délai de réponse médian, en heures. La médiane plutôt que la moyenne :
     * une seule demande oubliée pendant trois semaines fausserait une moyenne
     * au point de la rendre inutilisable.
     */
    private function medianResponseHours(User $provider): ?float
    {
        $delays = ServiceRequest::query()
            ->where('provider_id', $provider->id)
            ->whereNotNull('responded_at')
            ->get(['created_at', 'responded_at'])
            ->map(fn (ServiceRequest $r) => $r->created_at->diffInMinutes($r->responded_at))
            ->filter(fn ($minutes) => $minutes >= 0)
            ->sort()
            ->values();

        if ($delays->isEmpty()) {
            return null;
        }

        $middle = intdiv($delays->count(), 2);

        $median = $delays->count() % 2 === 0
            ? ($delays[$middle - 1] + $delays[$middle]) / 2
            : $delays[$middle];

        return round($median / 60, 1);
    }

    /**
     * @return Collection<int, int> note (1 à 5) => nombre d'avis
     */
    private function ratingBreakdown(User $provider): Collection
    {
        $counts = Review::query()
            ->where('provider_id', $provider->id)
            ->selectRaw('rating, count(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        return collect(range(5, 1))->mapWithKeys(
            fn (int $rating) => [$rating => (int) ($counts[$rating] ?? 0)],
        );
    }

    /**
     * @return Collection<int, array{title: string, requests: int}>
     */
    private function topServices(User $provider): Collection
    {
        return Service::query()
            ->where('provider_id', $provider->id)
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->take(5)
            ->get()
            ->map(fn (Service $service) => [
                'title' => $service->title,
                'requests' => $service->requests_count,
            ]);
    }
}
