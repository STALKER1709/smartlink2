<x-app-layout :titre="__('Mes litiges')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Mes signalements')" :subtitle="__('Ce que vous avez déclaré, et ce qui en a été décidé.')" />
    </x-slot>

    <div class="mx-auto w-full max-w-3xl px-margin-mobile py-8 md:px-margin-desktop">
        @if ($disputes->isEmpty())
            <x-empty-state :title="__('Aucun signalement.')"
                           :description="__('Si une prestation se passe mal, vous pouvez la signaler depuis la fiche de la demande.')" />
        @else
            <div class="flex flex-col gap-4">
                @foreach ($disputes as $dispute)
                    <article class="rounded-xl border border-outline-variant shadow-elevation-1 bg-surface p-4">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="rounded bg-surface-container-high px-2 py-1 font-label-sm text-label-sm uppercase tracking-wider text-on-surface">
                                {{ \App\Models\Dispute::reasonLabel($dispute->reason) }}
                            </span>
                            <span @class([
                                'rounded-full px-3 py-1 font-label-sm text-label-sm',
                                'bg-tertiary-container/30 text-tertiary' => $dispute->isOpen(),
                                'bg-primary-container/20 text-primary' => $dispute->status === \App\Models\Dispute::STATUS_RESOLVED,
                                'bg-surface-container-high text-on-surface-variant' => $dispute->status === \App\Models\Dispute::STATUS_REJECTED,
                            ])>{{ \App\Models\Dispute::statuses()[$dispute->status] ?? $dispute->status }}</span>
                        </div>

                        <p class="font-medium text-on-surface">
                            {{ $dispute->request?->service?->title ?? 'Demande directe' }}
                            <span class="font-label-numeric text-label-md text-on-surface-variant">· n° {{ $dispute->id }}</span>
                        </p>
                        <p class="mt-1 line-clamp-3 text-label-md text-on-surface-variant">{{ $dispute->description }}</p>

                        @if ($dispute->resolution)
                            {{-- La décision est rendue au déclarant, pas seulement
                                 consignée : « résolu » sans un mot ne dit rien. --}}
                            <div class="mt-3 rounded-lg border border-outline-variant bg-surface-container-low p-3">
                                <p class="mb-1 font-button-text text-button-text text-on-surface">{{ __("Réponse de l'équipe") }}</p>
                                <p class="text-label-md text-on-surface-variant">{{ $dispute->resolution }}</p>
                            </div>
                        @endif

                        <p class="mt-3 font-label-sm text-label-sm text-on-surface-variant">
                            Déposé le {{ $dispute->created_at->translatedFormat('j F Y') }}
                            @if ($dispute->reviewed_at)
                                · tranché le {{ $dispute->reviewed_at->translatedFormat('j F Y') }}
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $disputes->links() }}</div>
        @endif
    </div>
</x-app-layout>
