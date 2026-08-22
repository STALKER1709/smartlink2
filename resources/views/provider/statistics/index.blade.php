<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.stats.title') }}</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @if ($stats['requests_total'] === 0)
            <p class="text-gray-500">{{ __('ui.stats.no_data') }}</p>
        @else
            {{-- Les quatre chiffres qui comptent --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['label' => __('ui.stats.requests_total'), 'value' => $stats['requests_total'], 'hint' => __('ui.stats.requests_recent', ['days' => $stats['period_days']]).' : '.$stats['requests_recent']],
                    ['label' => __('ui.stats.acceptance_rate'), 'value' => $stats['acceptance_rate'] === null ? __('ui.stats.not_enough') : $stats['acceptance_rate'].' %', 'hint' => __('ui.stats.acceptance_hint')],
                    ['label' => __('ui.stats.completion_rate'), 'value' => $stats['completion_rate'] === null ? __('ui.stats.not_enough') : $stats['completion_rate'].' %', 'hint' => __('ui.stats.completion_hint')],
                    ['label' => __('ui.stats.response_time'), 'value' => $stats['median_response_hours'] === null ? __('ui.stats.not_enough') : $stats['median_response_hours'].' '.__('ui.stats.hours'), 'hint' => __('ui.stats.response_hint')],
                ] as $tile)
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $tile['label'] }}</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900 tabular-nums">{{ $tile['value'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $tile['hint'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Répartition des demandes par statut --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-900">{{ __('ui.stats.by_status') }}</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ($stats['by_status'] as $status => $count)
                            @php $share = (int) round($count / max($stats['requests_total'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-28 shrink-0"><x-status-badge :status="$status" /></span>
                                <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-brand-500" style="width: {{ max($share, 2) }}%"></div>
                                </div>
                                <span class="w-10 text-right text-sm text-gray-600 tabular-nums">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Réputation --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-900">{{ __('ui.stats.rating_breakdown') }}</h3>

                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="text-3xl font-semibold text-gray-900 tabular-nums">
                            {{ $stats['rating_count'] > 0 ? number_format($stats['rating_avg'], 1, ',', ' ') : __('ui.stats.not_enough') }}
                        </span>
                        <span class="text-sm text-gray-500">{{ __('ui.stats.rating_count', ['count' => $stats['rating_count']]) }}</span>
                    </div>

                    <div class="mt-4 space-y-2">
                        @foreach ($stats['rating_breakdown'] as $rating => $count)
                            @php $share = (int) round($count / max($stats['rating_count'], 1) * 100); @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-12 shrink-0 text-sm text-gray-600 tabular-nums">{{ $rating }} ★</span>
                                <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $count > 0 ? max($share, 2) : 0 }}%"></div>
                                </div>
                                <span class="w-10 text-right text-sm text-gray-600 tabular-nums">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Services les plus demandés --}}
            @if ($stats['top_services']->isNotEmpty())
                <div class="bg-white rounded-lg border border-gray-200 p-5">
                    <h3 class="font-semibold text-gray-900">{{ __('ui.stats.top_services') }}</h3>
                    <ul class="mt-4 divide-y divide-gray-100">
                        @foreach ($stats['top_services'] as $service)
                            <li class="flex items-center justify-between gap-4 py-2">
                                <span class="text-sm text-gray-800 truncate">{{ $service['title'] }}</span>
                                <span class="text-sm font-medium text-gray-600 tabular-nums shrink-0">{{ $service['requests'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
