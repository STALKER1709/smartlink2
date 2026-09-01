<x-app-layout :titre="__('Mes statistiques')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.stats.title')"
                       :subtitle="__('Aperçu de vos performances sur les :days derniers jours.', ['days' => $stats['period_days']])">
            {{-- La pastille de période de la maquette : la fenêtre observée
                 est la première chose à savoir devant un chiffre. --}}
            <x-slot name="action">
                <span class="inline-flex items-center gap-2 rounded-full bg-surface-container-high px-4 py-2 font-label-md text-label-md text-on-surface-variant">
                    <x-icon name="calendar_month" size="sm" />
                    {{ __(':days derniers jours', ['days' => $stats['period_days']]) }}
                </span>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8 space-y-6">

        @if ($stats['requests_total'] === 0)
            <p class="text-on-surface-variant">{{ __('ui.stats.no_data') }}</p>
        @else
            {{-- Les quatre chiffres qui comptent, à la forme des maquettes :
                 pictogramme dans une pastille, libellé, valeur, et une puce de
                 tendance quand la période précédente permet de comparer. --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                {{-- Des clés nommées, et non un tuple : `done_all` écrit en
                     tête de tableau positionnel échappait au relevé des
                     icônes, et s'affichait « DONE_ALL » en toutes lettres dans
                     la pastille. `IconSubsetTest` ne le voyait pas davantage —
                     il lit le dépôt avec le même relevé. --}}
                @foreach ([
                    ['icone' => 'mail', 'libelle' => __('ui.stats.requests_total'), 'valeur' => $stats['requests_total'], 'indice' => __('ui.stats.requests_recent', ['days' => $stats['period_days']]).' : '.$stats['requests_recent'], 'tendance' => $stats['trends']['requests_recent'] ?? null],
                    ['icone' => 'check_circle', 'libelle' => __('ui.stats.acceptance_rate'), 'valeur' => $stats['acceptance_rate'] === null ? __('ui.stats.not_enough') : $stats['acceptance_rate'].' %', 'indice' => __('ui.stats.acceptance_hint'), 'tendance' => null],
                    ['icone' => 'done_all', 'libelle' => __('ui.stats.completion_rate'), 'valeur' => $stats['completion_rate'] === null ? __('ui.stats.not_enough') : $stats['completion_rate'].' %', 'indice' => __('ui.stats.completion_hint'), 'tendance' => null],
                    ['icone' => 'schedule', 'libelle' => __('ui.stats.response_time'), 'valeur' => $stats['median_response_hours'] === null ? __('ui.stats.not_enough') : $stats['median_response_hours'].' '.__('ui.stats.hours'), 'indice' => __('ui.stats.response_hint'), 'tendance' => null],
                ] as $tuile)
                    @php
                        ['icone' => $icone, 'libelle' => $libelle, 'valeur' => $valeur, 'indice' => $indice, 'tendance' => $tendance] = $tuile;
                    @endphp
                    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest p-4 shadow-elevation-1 transition-shadow duration-150 hover:shadow-elevation-2 md:p-6">
                        <div class="mb-4 flex items-start justify-between gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-container text-on-primary-container">
                                <x-icon :name="$icone" />
                            </span>

                            @if ($tendance !== null)
                                <span @class([
                                    'flex shrink-0 items-center gap-1 rounded-full px-2 py-1',
                                    'bg-primary-container/15 text-primary' => $tendance >= 0,
                                    'bg-error-container/30 text-error' => $tendance < 0,
                                ])>
                                    <x-icon :name="$tendance >= 0 ? 'trending_up' : 'trending_down'" size="xs" />
                                    <span class="font-label-numeric text-label-sm">{{ $tendance > 0 ? '+' : '' }}{{ $tendance }} %</span>
                                </span>
                            @endif
                        </div>

                        <p class="font-label-md text-label-md text-on-surface-variant">{{ $libelle }}</p>
                        {{-- Le palier d'affichage, comme dans la maquette : ces
                             quatre chiffres sont le sujet de l'écran, et au
                             palier de titre ils pesaient autant que le titre de
                             la section d'à côté. --}}
                        <p class="mt-1 font-label-numeric text-headline-lg text-on-surface md:text-display-lg">{{ $valeur }}</p>
                        <p class="mt-1 font-body-md text-label-sm text-on-surface-variant">{{ $indice }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Répartition des demandes par statut --}}
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-elevation-1 p-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.by_status') }}</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ($stats['by_status'] as $status => $count)
                            @php $share = (int) round($count / max($stats['requests_total'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-28 shrink-0"><x-status-badge :status="$status" /></span>
                                <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                                    <div class="h-full rounded-full bg-primary" style="width: {{ max($share, 2) }}%"></div>
                                </div>
                                <span class="w-10 text-right font-label-numeric text-label-md text-on-surface-variant">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Réputation --}}
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-elevation-1 p-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.rating_breakdown') }}</h3>

                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="font-label-numeric text-headline-md text-on-surface">
                            {{ $stats['rating_count'] > 0 ? number_format($stats['rating_avg'], 1, ',', ' ') : __('ui.stats.not_enough') }}
                        </span>
                        <span class="text-label-md text-on-surface-variant">{{ __('ui.stats.rating_count', ['count' => $stats['rating_count']]) }}</span>
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach ($stats['rating_breakdown'] as $rating => $count)
                            @php $share = (int) round($count / max($stats['rating_count'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="flex w-12 shrink-0 items-center gap-1 text-on-surface-variant">
                                    <span class="font-label-numeric text-label-md">{{ $rating }}</span>
                                    <x-icon name="star" size="xs" filled class="text-secondary-container" />
                                </span>
                                <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                                    {{-- L'or de Google (#fbbc04) traînait ici : dans un monde vert et
                                         crème, une couleur qui n'appartient à aucun jeton se voit. --}}
                                    <div class="h-full rounded-full bg-primary" style="width: {{ $count > 0 ? max($share, 2) : 0 }}%"></div>
                                </div>
                                <span class="w-10 text-right font-label-numeric text-label-md text-on-surface-variant">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Services les plus demandés --}}
            @if ($stats['top_services']->isNotEmpty())
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-elevation-1 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.top_services') }}</h3>
                        <a href="{{ route('provider.services.index') }}" class="inline-flex min-h-6 items-center font-label-md text-label-md text-primary hover:underline">{{ __('Voir tout') }}</a>
                    </div>

                    {{-- La rangée de la maquette : pastille du métier, titre,
                         nombre de demandes en toutes lettres. Le chiffre seul à
                         droite ne disait pas ce qu'il comptait. --}}
                    <ul class="mt-4 divide-y divide-outline-variant">
                        @foreach ($stats['top_services'] as $service)
                            <li class="flex items-center gap-4 py-3">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-surface-container-high text-primary">
                                    <x-icon name="handyman" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-label-md text-label-md font-semibold text-on-surface">{{ $service['title'] }}</span>
                                    <span class="block font-body-md text-label-sm text-on-surface-variant">
                                        <span class="font-label-numeric">{{ $service['requests'] }}</span>
                                        {{ trans_choice('demande reçue|demandes reçues', $service['requests']) }}
                                    </span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
