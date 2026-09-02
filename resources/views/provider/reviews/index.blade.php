<x-app-layout :titre="__('Mes avis')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Mes avis')"
                       :subtitle="$ratingCount > 0 ? trans_choice(':count avis reçu|:count avis reçus', $ratingCount) : null" />
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-6 md:px-margin-tablet lg:px-margin-desktop">
        {{-- La note d'ensemble et sa répartition, côte à côte : un tiers pour
             le chiffre, deux tiers pour les barres. --}}
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="flex flex-col items-center justify-center rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-6 text-center md:col-span-1">
                <span class="mb-2 font-display-lg text-headline-lg text-primary sm:text-display-lg">{{ number_format((float) $ratingAvg, 1, ',', ' ') }}</span>
                <x-star-rating :rating="$ratingAvg" :afficher-note="false" class="mb-2" />
                <p class="font-body-md text-on-surface-variant">
                    @if ($ratingCount > 0)
                        Basé sur <span class="font-label-numeric">{{ $ratingCount }}</span> {{ Str::plural('avis', $ratingCount) }}
                    @else
                        Pas encore d'avis
                    @endif
                </p>
            </div>

            <div class="flex flex-col justify-center rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-6 md:col-span-2">
                <div class="space-y-3">
                    @foreach ($ratingBreakdown as $rating => $count)
                        @php $part = (int) round($count / max($ratingCount, 1) * 100); @endphp
                        <div class="flex items-center gap-3">
                            <span class="w-4 text-right font-label-numeric text-on-surface">{{ $rating }}</span>
                            <x-icon name="star" size="sm" filled class="text-secondary-container" />
                            <div class="h-2 flex-grow overflow-hidden rounded-full bg-surface-variant">
                                <div class="h-full bg-primary" style="width: {{ $count > 0 ? max($part, 2) : 0 }}%"></div>
                            </div>
                            <span class="w-8 text-right font-label-numeric text-on-surface-variant">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($reviews->isEmpty())
            <x-empty-state :title="__('Aucun avis pour le moment.')" :description="__('Les clients laissent un avis après une prestation terminée.')" />
        @else
            <div class="space-y-6">
                @foreach ($reviews as $review)
                    @php $nom = $review->client?->clientProfile?->fullName() ?? $review->client?->name; @endphp
                    <div class="rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-6">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-variant font-semibold text-on-surface-variant">
                                    {{ Str::upper(Str::substr($nom ?? '?', 0, 2)) }}
                                </span>
                                <div class="min-w-0">
                                    <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $nom }}</h3>
                                    <p class="font-label-md text-label-md text-on-surface-variant">{{ $review->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <x-star-rating :rating="$review->rating" :afficher-note="false" class="shrink-0" />
                        </div>
                        @if ($review->comment)
                            <p class="font-body-md text-body-md text-on-surface">{{ $review->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
</x-app-layout>
