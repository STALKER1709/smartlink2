<x-app-layout :titre="__('Paliers d\'abonnement')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.admin_plans.title')" />
    </x-slot>

    <div class="max-w-4xl mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        <p class="prose-measure mb-6 text-body-md text-on-surface-variant">{{ __('ui.admin_plans.intro') }}</p>

        <x-list-panel>
            @foreach ($plans as $plan)
                <x-list-row class="flex items-start gap-4 sm:gap-6">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <h3 class="font-headline-md text-headline-md text-on-surface">{{ $plan->name() }}</h3>
                            @unless ($plan->is_active)
                                <x-status-badge status="inactive" />
                            @endunless
                        </div>

                        <p class="mt-1 font-label-numeric text-headline-md text-on-surface">{{ $plan->formattedPrice() }}</p>

                        {{-- Une ligne par limite. Réunies sur une seule
                             ligne séparée par des points médians, elles
                             débordaient à 390 px et laissaient « : Illimité »
                             orphelin sur la ligne suivante.

                             La chasse fixe ne va qu'aux chiffres : « Illimité »
                             composé en JetBrains Mono se lisait comme une
                             valeur machine. --}}
                        <dl class="mt-2 space-y-0.5 text-label-md text-on-surface-variant">
                            @foreach ([
                                [__('ui.admin_plans.max_services'), $plan->allowsUnlimitedServices() ? null : $plan->max_services],
                                [__('ui.admin_plans.max_requests'), $plan->allowsUnlimitedRequests() ? null : $plan->max_monthly_requests],
                                [__('ui.admin_plans.subscribers'), $plan->active_subscriptions_count],
                            ] as [$libelle, $valeur])
                                <div>
                                    {{-- Le deux-points reste dans la police du
                                         texte : composé en chasse fixe avec la
                                         valeur, il ouvrait un blanc de deux
                                         caractères avant chaque chiffre. --}}
                                    <dt class="inline">{{ $libelle }}&nbsp;:</dt>
                                    <dd class="inline font-medium text-on-surface {{ $valeur === null ? '' : 'font-label-numeric' }}">{{ $valeur ?? __('ui.admin_plans.unlimited') }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <a href="{{ route('admin.plans.edit', $plan) }}" class="shrink-0 self-start pt-0.5 text-label-md font-medium text-primary hover:text-primary-container">
                        {{ __('ui.admin_plans.edit') }}
                    </a>
                </x-list-row>
            @endforeach
        </x-list-panel>
    </div>
</x-app-layout>
