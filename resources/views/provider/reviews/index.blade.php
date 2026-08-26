<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mes avis"
                       :subtitle="$ratingCount > 0 ? $ratingCount.' '.Str::plural('avis reçu', $ratingCount) : null" />
    </x-slot>

    <div class="max-w-3xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        {{-- La note était écrite deux fois côte à côte : « 4,0 » en grand,
             puis « 4,0 (2 avis) » juste après. Les étoiles ne portent plus
             que le décompte. --}}
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <div class="flex items-baseline gap-3">
                <span class="font-label-numeric text-3xl text-on-surface">{{ number_format((float) $ratingAvg, 1, ',', ' ') }}</span>
                <x-star-rating :rating="$ratingAvg" :count="$ratingCount" :afficher-note="false" />
            </div>

            <div class="mt-4 space-y-2">
                @foreach ($ratingBreakdown as $rating => $count)
                    @php $share = (int) round($count / max($ratingCount, 1) * 100); @endphp
                    <div class="flex items-center gap-3">
                        <span class="flex w-10 shrink-0 items-center gap-1 text-sm text-on-surface-variant">
                            {{ $rating }}
                            <span class="material-symbols-outlined text-sm text-tertiary-container" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">star</span>
                        </span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-container">
                            <div class="h-full rounded-full bg-primary" style="width: {{ $count > 0 ? max($share, 2) : 0 }}%"></div>
                        </div>
                        <span class="w-8 text-right font-label-numeric text-sm text-on-surface-variant">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-8">
            @if ($reviews->isEmpty())
                <x-empty-state title="Aucun avis pour le moment." description="Les clients laissent un avis après une prestation terminée." />
            @else
                {{-- Chaque avis était une carte bordée posée sur du gris. Le
                     commentaire est ce qu'on vient lire : il porte la rangée,
                     le nom et la note l'annoncent. --}}
                <x-list-panel>
                    @foreach ($reviews as $review)
                        <x-list-row>
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-container/20 font-semibold text-primary">
                                    {{ Str::substr($review->client?->clientProfile?->fullName() ?? $review->client?->name ?? '?', 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                        <p class="font-medium text-on-surface">{{ $review->client?->clientProfile?->fullName() ?? $review->client?->name }}</p>
                                        <x-star-rating :rating="$review->rating" class="shrink-0" />
                                    </div>
                                    <p class="mt-0.5 text-sm text-on-surface-variant">{{ $review->created_at->diffForHumans() }}</p>
                                    @if ($review->comment)
                                        <p class="mt-2 text-body-md text-on-surface">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-list-row>
                    @endforeach
                </x-list-panel>

                <div class="mt-6">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
