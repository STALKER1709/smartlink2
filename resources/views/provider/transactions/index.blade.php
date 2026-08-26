@php
    $statusMeta = [
        \App\Models\Payment::STATUS_SUCCESS => ['label' => 'Réglé', 'class' => 'bg-secondary-container/40 text-on-secondary-container'],
        \App\Models\Payment::STATUS_PENDING => ['label' => 'En attente', 'class' => 'bg-tertiary-container/20 text-tertiary'],
        \App\Models\Payment::STATUS_FAILED => ['label' => 'Échec', 'class' => 'bg-error-container text-on-error-container'],
        \App\Models\Payment::STATUS_CANCELLED => ['label' => 'Annulé', 'class' => 'bg-surface-container-high text-on-surface-variant'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Historique des transactions" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <p class="prose-measure mb-6 text-body-md text-on-surface-variant">
            Vos paiements d'abonnement SmartLink. SmartLink ne traite aucun paiement entre vous et vos clients.
        </p>

        @if ($payments->isEmpty())
            <x-empty-state title="Aucune transaction pour le moment." description="Vos règlements d'abonnement apparaîtront ici." />
        @else
            {{-- C'était une grille de douze colonnes qui se dépliait en cinq
                 blocs empilés sur mobile, la référence en tête de chaque
                 ligne, juste sous le nom du palier — devant la date, le
                 montant et le statut, qui sont ce qu'on vient voir.

                 Ce qu'un prestataire vient vérifier tient en trois choses :
                 quand, combien, et si c'est passé. La référence SmartLink
                 descend d'un cran : elle ne sert qu'à écrire au support. --}}
            <x-list-panel>
                @foreach ($payments as $payment)
                    @php $meta = $statusMeta[$payment->status] ?? $statusMeta[\App\Models\Payment::STATUS_PENDING]; @endphp
                    <x-list-row class="flex items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-medium text-on-surface">{{ $payment->plan?->name() ?? 'Abonnement' }}</p>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-button-text text-xs font-semibold {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </div>
                            <p class="mt-0.5 font-label-numeric text-sm text-on-surface-variant">
                                {{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}
                            </p>
                            @if ($payment->failure_reason)
                                <p class="mt-0.5 text-sm text-error">{{ $payment->failure_reason }}</p>
                            @endif
                            {{-- Référence et numéro débité sur la même ligne :
                                 séparés, le point médian de la date restait
                                 orphelin en bout de ligne à 390 px. --}}
                            @if ($payment->internal_reference || $payment->phone)
                                <p class="mt-1 font-label-numeric text-xs text-on-surface-variant">
                                    @if ($payment->internal_reference)Réf. {{ $payment->internal_reference }}@endif
                                    @if ($payment->internal_reference && $payment->phone) · @endif
                                    {{ $payment->phone }}
                                </p>
                            @endif
                        </div>

                        {{-- Les montants forment une colonne : alignés à
                             droite en chasse fixe, ils se comparent d'une
                             ligne à l'autre sans lecture. --}}
                        <p class="shrink-0 font-label-numeric font-semibold text-on-surface">{{ $payment->formattedAmount() }}</p>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
