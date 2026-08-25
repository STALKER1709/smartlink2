<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mes avis" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 mb-8">
            <div class="flex items-baseline gap-3">
                <span class="font-label-numeric text-3xl text-on-surface">{{ number_format((float) $ratingAvg, 1, ',', ' ') }}</span>
                <x-star-rating :rating="$ratingAvg" :count="$ratingCount" />
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($ratingBreakdown as $rating => $count)
                    @php $share = (int) round($count / max($ratingCount, 1) * 100); @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-10 shrink-0 text-sm text-on-surface-variant flex items-center gap-1">
                            {{ $rating }}
                            <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                        </span>
                        <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $count > 0 ? max($share, 2) : 0 }}%"></div>
                        </div>
                        <span class="w-8 text-right font-label-numeric text-sm text-on-surface-variant">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($reviews->isEmpty())
            <x-empty-state icon="reviews" title="Aucun avis pour le moment." description="Les clients laissent un avis après une prestation terminée." />
        @else
            <div class="space-y-4">
                @foreach ($reviews as $review)
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary-container/20 text-primary flex items-center justify-center font-semibold shrink-0">
                                    {{ Str::substr($review->client?->clientProfile?->fullName() ?? $review->client?->name ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-medium text-on-surface">{{ $review->client?->clientProfile?->fullName() ?? $review->client?->name }}</h3>
                                    <p class="font-label-numeric text-xs text-on-surface-variant">{{ $review->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <x-star-rating :rating="$review->rating" />
                        </div>
                        @if ($review->comment)
                            <p class="text-sm text-on-surface">{{ $review->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
