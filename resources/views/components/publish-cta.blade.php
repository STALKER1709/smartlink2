@props(['compact' => false])

@php
    /*
     * Le bouton « Publier un service » menait à une page 403 nue dès que le
     * prestataire atteignait le plafond de son palier — c'est-à-dire au moment
     * précis où il en avait le plus besoin. La règle est celle de la Policy,
     * pas une seconde vérité : `@can('create', Service::class)` interroge
     * exactement ce qui autorisera l'enregistrement.
     *
     * Au plafond, le bouton ne disparaît pas — un prestataire qui ne trouve
     * plus « Publier » croit à une panne. Il dit ce qui bloque et mène là où
     * cela se débloque.
     */
    $prestataire = auth()->user();
    $plan = $prestataire?->currentPlan();
    $plafond = $plan && ! $plan->allowsUnlimitedServices() ? $plan->max_services : null;
@endphp

@if ($prestataire?->isProvider())
@can('create', \App\Models\Service::class)
    <a href="{{ route('provider.services.create') }}"
       {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container']) }}>
        <span class="material-symbols-outlined text-base" aria-hidden="true">add</span>
        {{ __("Publier un service") }}
    </a>
@else
    <div class="flex flex-col items-start gap-1">
        <a href="{{ route('provider.subscription.show') }}"
           {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border border-tertiary px-4 py-2 font-button-text text-sm font-semibold text-tertiary transition-colors hover:bg-tertiary-container/20']) }}>
            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_upward</span>
            {{ __("Augmenter mon plafond") }}
        </a>
        @unless ($compact)
            <p class="text-xs text-on-surface-variant">
                @if ($plafond !== null)
                    {!! __('Vous publiez déjà <span class="font-label-numeric">:nombre</span> :services, le maximum de votre palier.', [
                        'nombre' => $plafond,
                        'services' => trans_choice('service|services', $plafond),
                    ]) !!}
                @else
                    {{ __('Votre abonnement ne permet plus de publier.') }}
                @endif
            </p>
        @endunless
    </div>
@endcan
@endif
