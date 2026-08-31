@php
    $statusMeta = [
        \App\Models\Payment::STATUS_SUCCESS => ['label' => __('Réglé'), 'class' => 'bg-primary-container/20 text-primary'],
        \App\Models\Payment::STATUS_PENDING => ['label' => __('En attente'), 'class' => 'bg-secondary-container/25 text-on-secondary-container'],
        \App\Models\Payment::STATUS_FAILED => ['label' => __('Échec'), 'class' => 'bg-error-container text-on-error-container'],
        \App\Models\Payment::STATUS_CANCELLED => ['label' => __('Annulé'), 'class' => 'bg-surface-container-high text-on-surface-variant'],
    ];
@endphp

<x-app-layout :titre="__('Mes transactions')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Historique des transactions')" :subtitle="__('Vos règlements d\'abonnement SmartLink.')">
            @if ($payments->isNotEmpty())
                <x-slot name="action">
                    <a href="{{ route('provider.transactions.export') }}"
                       class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container md:w-auto">
                        <x-icon name="download" />
                        {{ __("Exporter (CSV)") }}
                    </a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        {{-- Les maquettes posent « Gains ce mois » et « En attente » en tête :
             deux chiffres d'une place de marché qui encaisse pour le
             prestataire. SmartLink ne voit jamais un franc de la prestation.
             Les trois chiffres portent donc sur ce qui existe — les
             abonnements réglés. --}}
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            {{-- Des clés nommées, et non un tuple : une ligature écrite en
                 position dans un tableau échappe au relevé des icônes, et
                 s'affiche alors en toutes lettres. --}}
            @foreach ([
                ['libelle' => 'Réglé cette année', 'montant' => $regleCetteAnnee, 'icone' => 'trending_up', 'teinte' => 'text-primary'],
                ['libelle' => 'En attente', 'montant' => $enAttente, 'icone' => 'pending', 'teinte' => 'text-secondary'],
                ['libelle' => 'Total versé à SmartLink', 'montant' => $totalVerse, 'icone' => 'receipt_long', 'teinte' => 'text-on-surface-variant'],
            ] as $tuile)
                @php
                    ['libelle' => $libelle, 'montant' => $montant, 'icone' => $icone, 'teinte' => $teinte] = $tuile;
                @endphp
                <div class="flex h-[120px] flex-col justify-between rounded-xl border border-outline-variant shadow-elevation-1 bg-surface p-4">
                    <div class="flex items-center justify-between gap-2 text-on-surface-variant">
                        <span class="text-label-md">{{ $libelle }}</span>
                        <x-icon :name="$icone" class="{{ $teinte }}" />
                    </div>
                    <div class="font-label-numeric text-headline-lg text-on-background">
                        {{ number_format($montant, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            @endforeach
        </div>

        <p class="prose-measure mb-6 text-body-md text-on-surface-variant">
            {{ __("SmartLink ne traite aucun paiement entre vous et vos clients : seul votre abonnement transite ici.") }}
        </p>

        @if ($payments->isEmpty())
            <x-empty-state :title="__('Aucune transaction pour le moment.')" :description="__('Vos règlements d\'abonnement apparaîtront ici.')" />
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
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 font-button-text text-label-sm font-semibold {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </div>
                            <p class="mt-0.5 font-label-numeric text-label-md text-on-surface-variant">
                                {{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}
                            </p>
                            @if ($payment->failure_reason)
                                <p class="mt-0.5 text-label-md text-error">{{ $payment->failure_reason }}</p>
                            @endif
                            {{-- Référence et numéro débité sur la même ligne :
                                 séparés, le point médian de la date restait
                                 orphelin en bout de ligne à 390 px. --}}
                            @if ($payment->internal_reference || $payment->phone)
                                <p class="mt-1 font-label-numeric text-label-sm text-on-surface-variant">
                                    @if ($payment->internal_reference){{ __('Réf.') }} {{ $payment->internal_reference }}@endif
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
