<x-app-layout :titre="__('Mes statistiques')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.stats.title')"
                       :subtitle="__('Aperçu de vos performances sur les :days derniers jours.', ['days' => $stats['period_days']])" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8 space-y-6">

        @if ($stats['requests_total'] === 0)
            <p class="text-on-surface-variant">{{ __('ui.stats.no_data') }}</p>
        @else
            {{-- Les quatre chiffres qui comptent, à la forme des maquettes :
                 pictogramme dans une pastille, libellé, valeur, et une puce de
                 tendance quand la période précédente permet de comparer. --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    ['mail', __('ui.stats.requests_total'), $stats['requests_total'], __('ui.stats.requests_recent', ['days' => $stats['period_days']]).' : '.$stats['requests_recent'], $stats['trends']['requests_recent'] ?? null],
                    ['check_circle', __('ui.stats.acceptance_rate'), $stats['acceptance_rate'] === null ? __('ui.stats.not_enough') : $stats['acceptance_rate'].' %', __('ui.stats.acceptance_hint'), null],
                    ['done_all', __('ui.stats.completion_rate'), $stats['completion_rate'] === null ? __('ui.stats.not_enough') : $stats['completion_rate'].' %', __('ui.stats.completion_hint'), null],
                    ['schedule', __('ui.stats.response_time'), $stats['median_response_hours'] === null ? __('ui.stats.not_enough') : $stats['median_response_hours'].' '.__('ui.stats.hours'), __('ui.stats.response_hint'), null],
                ] as [$icone, $libelle, $valeur, $indice, $tendance])
                    <div class="flex flex-col items-start justify-between gap-4 rounded-xl border border-outline-variant bg-surface p-4 transition-colors hover:border-primary md:p-6">
                        <span class="rounded-full bg-surface-container-high p-2">
                            <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">{{ $icone }}</span>
                        </span>

                        <div>
                            <p class="mb-1 font-body-md text-body-md text-on-surface-variant">{{ $libelle }}</p>
                            <p class="font-label-numeric text-headline-xl text-on-background">{{ $valeur }}</p>
                            <p class="mt-1 text-label-md text-on-surface-variant">{{ $indice }}</p>
                        </div>

                        @if ($tendance !== null)
                            <span @class([
                                'flex items-center gap-1 rounded px-2 py-1',
                                'bg-secondary-container/30 text-primary' => $tendance >= 0,
                                'bg-error-container/30 text-error' => $tendance < 0,
                            ])>
                                <span class="material-symbols-outlined text-[14px]" aria-hidden="true">{{ $tendance >= 0 ? 'trending_up' : 'trending_down' }}</span>
                                <span class="font-label-numeric text-label-md">{{ $tendance > 0 ? '+' : '' }}{{ $tendance }} %</span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Répartition des demandes par statut --}}
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.by_status') }}</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ($stats['by_status'] as $status => $count)
                            @php $share = (int) round($count / max($stats['requests_total'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-28 shrink-0"><x-status-badge :status="$status" /></span>
                                <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                                    <div class="h-full rounded-full bg-primary" style="width: {{ max($share, 2) }}%"></div>
                                </div>
                                <span class="w-10 text-right font-label-numeric text-label-lg text-on-surface-variant">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Réputation --}}
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.rating_breakdown') }}</h3>

                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="font-label-numeric text-headline-md text-on-surface">
                            {{ $stats['rating_count'] > 0 ? number_format($stats['rating_avg'], 1, ',', ' ') : __('ui.stats.not_enough') }}
                        </span>
                        <span class="text-label-lg text-on-surface-variant">{{ __('ui.stats.rating_count', ['count' => $stats['rating_count']]) }}</span>
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach ($stats['rating_breakdown'] as $rating => $count)
                            @php $share = (int) round($count / max($stats['rating_count'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-12 shrink-0 font-label-numeric text-label-lg text-on-surface-variant">{{ $rating }} ★</span>
                                <div class="flex-1 h-2 rounded-full bg-surface-container overflow-hidden">
                                    {{-- L'or de Google (#fbbc04) traînait ici : dans un monde vert et
                                         crème, une couleur qui n'appartient à aucun jeton se voit. --}}
                                    <div class="h-full rounded-full bg-primary" style="width: {{ $count > 0 ? max($share, 2) : 0 }}%"></div>
                                </div>
                                <span class="w-10 text-right font-label-numeric text-label-lg text-on-surface-variant">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Services les plus demandés --}}
            @if ($stats['top_services']->isNotEmpty())
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-5">
                    <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.stats.top_services') }}</h3>
                    <ul class="mt-4 divide-y divide-outline-variant">
                        @foreach ($stats['top_services'] as $service)
                            <li class="flex items-center justify-between gap-4 py-2">
                                <span class="text-label-lg text-on-surface truncate">{{ $service['title'] }}</span>
                                <span class="font-label-numeric text-label-lg text-on-surface-variant shrink-0">{{ $service['requests'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
