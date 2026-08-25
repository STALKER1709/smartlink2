@php
    $statusMeta = [
        \App\Models\Payment::STATUS_SUCCESS => ['label' => 'Succès', 'class' => 'bg-secondary-container/40 text-on-secondary-container', 'icon' => 'check_circle', 'iconBg' => 'bg-secondary-container text-on-secondary-container'],
        \App\Models\Payment::STATUS_PENDING => ['label' => 'En attente', 'class' => 'bg-tertiary-container/20 text-tertiary', 'icon' => 'hourglass_empty', 'iconBg' => 'bg-surface-container-high text-on-surface-variant'],
        \App\Models\Payment::STATUS_FAILED => ['label' => 'Échec', 'class' => 'bg-error-container text-on-error-container', 'icon' => 'error', 'iconBg' => 'bg-error-container text-on-error-container'],
        \App\Models\Payment::STATUS_CANCELLED => ['label' => 'Annulé', 'class' => 'bg-surface-container-high text-on-surface-variant', 'icon' => 'block', 'iconBg' => 'bg-surface-container-high text-on-surface-variant'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Historique des transactions" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <p class="text-on-surface-variant mb-6">Vos paiements d'abonnement SmartLink. SmartLink ne traite aucun paiement entre vous et vos clients.</p>

        @if ($payments->isEmpty())
            <x-empty-state icon="receipt_long" title="Aucune transaction pour le moment." description="Vos règlements d'abonnement apparaîtront ici." />
        @else
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
                <div class="hidden md:grid grid-cols-12 gap-4 p-4 border-b border-outline-variant text-on-surface-variant text-sm font-semibold bg-surface-container-low">
                    <div class="col-span-5">Description</div>
                    <div class="col-span-3">Date</div>
                    <div class="col-span-2">Statut</div>
                    <div class="col-span-2 text-right">Montant</div>
                </div>

                <div class="divide-y divide-outline-variant">
                    @foreach ($payments as $payment)
                        @php $meta = $statusMeta[$payment->status] ?? $statusMeta[\App\Models\Payment::STATUS_PENDING]; @endphp
                        <div class="p-4 flex flex-col md:grid md:grid-cols-12 gap-2 md:gap-4 items-start md:items-center hover:bg-surface-container-low transition-colors">
                            <div class="flex items-center gap-3 md:col-span-5 w-full">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $meta['iconBg'] }}">
                                    <span class="material-symbols-outlined text-lg">{{ $meta['icon'] }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-on-surface truncate">{{ $payment->plan?->name() ?? 'Abonnement' }}</p>
                                    <p class="text-sm text-on-surface-variant truncate">{{ $payment->internal_reference }}</p>
                                </div>
                            </div>
                            <div class="text-sm text-on-surface-variant md:col-span-3">
                                {{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}
                            </div>
                            <div class="md:col-span-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </div>
                            <div class="md:col-span-2 text-right font-label-numeric font-bold text-on-surface">
                                {{ $payment->formattedAmount() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
