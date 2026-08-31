@php
    $statusLabels = \App\Support\RequestStatus::labels();
@endphp

<x-app-layout :titre="__('Administration')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Vue d\'ensemble')"
                       :subtitle="__('Supervision de la plateforme au :date', ['date' => now()->translatedFormat('j F Y')])" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        {{-- Chaque compteur mène là où l'on agit dessus. « Comptes suspendus »
             et « Demandes » n'ont pas d'écran filtré dédié : ils restent
             muets plutôt que de promettre un lien qui n'existe pas. --}}
        {{-- Les six compteurs de la maquette : libellé et pictogramme sur la
             première ligne, le chiffre en dessous, la tendance en pied.

             Les libellés sont courts, et c'est une contrainte de la grille :
             six tuiles de front font 178 px même sur un écran de 1 920 px, la
             colonne latérale déduite. « Comptes suspendus » y passait à la
             ligne, et trois libellés sur six s'alignaient en drapeau. --}}
        <x-stat-grid :cols="6">
            <x-stat-tile :label="__('Clients')" icon="person" tone="primary" :value="number_format($stats['clients'], 0, ',', ' ')"
                         :trend="$tendances['clients']"
                         :href="route('admin.users.index', ['role' => \App\Models\User::ROLE_CLIENT])" />
            <x-stat-tile :label="__('Prestataires')" icon="handyman" tone="secondary" :value="number_format($stats['providers'], 0, ',', ' ')"
                         :trend="$tendances['providers']"
                         :href="route('admin.users.index', ['role' => \App\Models\User::ROLE_PROVIDER])" />
            {{-- Le fond d'alerte ne se pose que s'il y a quelque chose à
                 signaler : à zéro suspension, la tuile rouge faisait lever la
                 tête pour rien, et c'est exactement le crédit qu'une alerte
                 dépense. --}}
            <x-stat-tile :label="__('Suspendus')" icon="block"
                         :tone="$stats['suspended_users'] > 0 ? 'error' : 'secondary'"
                         :alert="$stats['suspended_users'] > 0"
                         :value="number_format($stats['suspended_users'], 0, ',', ' ')" />
            <x-stat-tile :label="__('Services actifs')" icon="check_circle" tone="primary"
                         :value="number_format($stats['services_active'], 0, ',', ' ')"
                         :href="route('admin.services.index')" />
            <x-stat-tile :label="__('Services')" icon="home_repair_service" tone="secondary"
                         :value="number_format($stats['services_total'], 0, ',', ' ')"
                         :href="route('admin.services.index')" />
            <x-stat-tile :label="__('Demandes')" icon="inbox" tone="secondary"
                         :value="number_format($stats['requests_total'], 0, ',', ' ')" />
        </x-stat-grid>

        {{-- La grille de la maquette : le tableau des chiffres à gauche, les
             files d'attente à droite. C'était une rangée de pastilles portant
             chacune un compteur — elle disait combien, jamais qui, et deux de
             ses compteurs étaient comptés depuis la vue, requête comprise. --}}
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6 shadow-elevation-1">
                    <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("Demandes par statut") }}</h2>

                    @php $totalDemandes = max((int) $stats['requests_total'], 1); @endphp

                    <div class="mt-4 space-y-3">
                        @foreach ($statusLabels as $value => $label)
                            @php
                                $compte = $requestsByStatus[$value] ?? 0;
                                $part = (int) round($compte / $totalDemandes * 100);
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-28 shrink-0 font-label-md text-label-md text-on-surface-variant">{{ $label }}</span>
                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-container">
                                    <span class="block h-full rounded-full bg-primary" style="width: {{ $compte > 0 ? max($part, 2) : 0 }}%"></span>
                                </span>
                                <span class="w-10 shrink-0 text-right font-label-numeric text-label-md text-on-surface">{{ $compte }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6 shadow-elevation-1">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-headline-md text-headline-md text-on-surface">{{ __("Derniers utilisateurs inscrits") }}</h2>
                        <a href="{{ route('admin.users.index') }}" class="font-label-md text-label-md text-primary hover:underline">{{ __("Voir tout") }}</a>
                    </div>

                    <ul class="mt-4 divide-y divide-outline-variant">
                        @foreach ($recentUsers as $user)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <span class="min-w-0">
                                    <span class="block font-label-md text-label-md font-semibold text-on-surface">{{ $user->name }}</span>
                                    <span class="block truncate font-body-md text-label-sm text-on-surface-variant">{{ $user->email }}</span>
                                </span>
                                <span class="shrink-0 font-label-numeric text-label-sm text-on-surface-variant">{{ $user->created_at->format('d/m/Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Les trois files d'attente. Chacune dit son volume, montre ses
                 trois premières entrées, et mène à son écran. --}}
            <div class="flex flex-col gap-6">
                @php
                    $queues = [
                        [
                            'titre' => __('Vérifications d\'identité'),
                            'total' => $files['verifications']['total'],
                            'entrees' => $files['verifications']['entrees'],
                            'lien' => route('admin.verifications.index'),
                            'action' => __('Traiter les vérifications'),
                            'urgent' => false,
                        ],
                        [
                            'titre' => __('ui.moderation.title'),
                            'total' => $files['moderation']['total'],
                            'entrees' => $files['moderation']['entrees'],
                            'lien' => route('admin.moderation.index'),
                            'action' => __('Ouvrir la file de modération'),
                            'urgent' => true,
                        ],
                        [
                            'titre' => __('Litiges ouverts'),
                            'total' => $files['litiges']['total'],
                            'entrees' => $files['litiges']['entrees'],
                            'lien' => route('admin.disputes.index'),
                            'action' => __('Voir les litiges'),
                            'urgent' => true,
                        ],
                    ];
                @endphp

                @foreach ($queues as $file)
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-6 shadow-elevation-1">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $file['titre'] }}</h2>
                            <span @class([
                                'shrink-0 rounded-full px-2 py-1 font-label-numeric text-label-sm font-bold',
                                'bg-error-container text-on-error-container' => $file['urgent'] && $file['total'] > 0,
                                'bg-secondary-container text-on-secondary-container' => ! $file['urgent'] && $file['total'] > 0,
                                'bg-surface-container-high text-on-surface-variant' => $file['total'] === 0,
                            ])>{{ $file['total'] }}</span>
                        </div>

                        @if ($file['total'] === 0)
                            <p class="mt-3 font-body-md text-body-md text-on-surface-variant">{{ __("Rien à traiter.") }}</p>
                        @else
                            <ul class="mt-4 space-y-2">
                                @foreach ($file['entrees'] as $entree)
                                    <li class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-2">
                                        <p class="truncate font-label-md text-label-md text-on-surface">{{ $entree['libelle'] }}</p>
                                        <p class="font-body-md text-label-sm text-on-surface-variant">{{ $entree['date']?->translatedFormat('j F') }}</p>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ $file['lien'] }}"
                               class="mt-4 flex min-h-11 items-center justify-center rounded-full border border-primary px-4 font-button-text text-label-md font-semibold text-primary transition-colors hover:bg-primary-container/10">
                                {{ $file['action'] }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
