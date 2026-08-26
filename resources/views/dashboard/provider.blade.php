<x-app-layout>
    <x-slot name="header">
        {{-- L'action principale du prestataire appartient à l'en-tête de page.
             Descendue dans la barre de « Demandes récentes », elle passait sous
             ce titre sur mobile et semblait agir dessus, ce qu'elle ne fait
             pas. --}}
        <x-page-header :title="__('Bonjour :prenom', ['prenom' => Str::of(auth()->user()->name)->trim()->explode(' ')->first()])"
                       subtitle="Voici le résumé de votre activité.">
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @php
            // « Services publiés » et « Services actifs » occupaient deux tuiles
            // pour dire la même chose : ils ne diffèrent que lorsqu'un service
            // est masqué, et c'est ce seul cas qui mérite d'être écrit.
            $masques = max($servicesCount - $activeServicesCount, 0);
            $auPlafond = $plan !== null
                && ! $plan->allowsUnlimitedServices()
                && $servicesCount >= $plan->max_services;
            $indiceServices = match (true) {
                $masques > 0 => trans_choice('{1} :count masqué|[2,*] :count masqués', $masques, ['count' => $masques]),
                $plan === null => null,
                $plan->allowsUnlimitedServices() => 'Sans limite',
                default => 'sur '.$plan->max_services.' autorisés',
            };

            $joursRestants = $subscription?->daysRemaining() ?? 0;
            $abonnement = match (true) {
                $subscription === null => ['Expiré', 'Vos services ne sont plus visibles', 'error'],
                $plan?->isFree() => [$plan->name(), 'Reconduit automatiquement', 'secondary'],
                $subscription->isTrial() => [$plan?->name() ?? '—', 'Essai · '.$joursRestants.' j restants', $joursRestants <= 7 ? 'tertiary' : 'primary'],
                default => [$plan?->name() ?? '—', $joursRestants.' jours restants', $joursRestants <= 7 ? 'tertiary' : 'primary'],
            };
        @endphp

        <x-stat-grid>
            <x-stat-tile label="Services publiés" :value="$servicesCount"
                         :hint="$indiceServices" :hint-tone="$auPlafond ? 'tertiary' : null"
                         :href="route('provider.services.index')" />
            <x-stat-tile label="Demandes en attente" :value="$pendingCount" tone="tertiary"
                         :hint="$remainingRequests === null ? 'Lecture sans limite' : $remainingRequests.' lisibles ce mois'"
                         :href="route('requests.index', ['status' => 'sent'])" />
            <x-stat-tile label="Prestations terminées" :value="$counts['completed'] ?? 0" tone="primary"
                         :href="route('requests.index', ['status' => 'completed'])" />
            {{-- L'abonnement paie la plateforme : il a sa place parmi les
                 chiffres du prestataire, pas seulement dans un bandeau
                 d'alerte qui ne paraît qu'à sept jours de l'échéance. --}}
            <x-stat-tile label="Abonnement" :value="$abonnement[0]" :tone="$abonnement[2]" texte
                         :hint="$abonnement[1]" :href="route('provider.subscription.show')" />
        </x-stat-grid>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-headline-md text-headline-md text-on-surface">Demandes récentes</h3>
            <a href="{{ route('requests.index') }}" class="flex items-center gap-1 text-sm font-semibold text-primary hover:text-primary-container">
                Voir tout
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        @if ($requests->isEmpty())
            <x-empty-state
                class="mt-4"
                title="Vous n'avez pas encore reçu de demande."
                description="Publiez vos services et soignez votre profil : les clients vous trouveront par la recherche."
                action-label="Publier un service"
                :action-href="route('provider.services.create')"
            />
        @else
            {{-- Le titre de la demande ne se tronque plus : c'est par lui
                 que le prestataire reconnaît ce qu'on lui demande. La pastille
                 le suit sur la même ligne quand la place le permet, passe
                 dessous sinon. --}}
            <x-list-panel class="mt-4">
                @foreach ($requests as $serviceRequest)
                    <x-list-row>
                        <a href="{{ route('requests.show', $serviceRequest) }}" class="group flex items-start gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="font-medium text-on-surface group-hover:text-primary">
                                        {{ $serviceRequest->service?->title ?? 'Demande directe' }}
                                    </p>
                                    <x-status-badge :status="$serviceRequest->status" />
                                </div>
                                <p class="mt-0.5 text-sm text-on-surface-variant">
                                    {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                                    · <span class="font-label-numeric">{{ $serviceRequest->created_at->format('d/m/Y') }}</span>
                                </p>
                            </div>
                            <span class="material-symbols-outlined shrink-0 text-on-surface-variant transition-transform group-hover:translate-x-0.5" aria-hidden="true">chevron_right</span>
                        </a>
                    </x-list-row>
                @endforeach
            </x-list-panel>
        @endif
    </div>
</x-app-layout>
