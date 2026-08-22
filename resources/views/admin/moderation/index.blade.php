<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.moderation.title') }}</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-4">
        <p class="text-sm text-gray-600">{{ __('ui.moderation.intro') }}</p>

        @if ($reports->isEmpty())
            <p class="text-gray-500">{{ __('ui.moderation.empty') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($reports as $report)
                    @php
                        $content = $report->moderatable;
                        $isService = $content instanceof \App\Models\Service;
                    @endphp
                    <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    {{ $isService ? __('ui.moderation.service') : __('ui.moderation.review') }}
                                    · {{ __('ui.moderation.flagged_at') }} {{ $report->created_at->format('d/m/Y H:i') }}
                                </p>
                                <p class="mt-1 font-medium text-gray-900 truncate">
                                    @if ($content === null)
                                        <span class="text-gray-400">— supprimé —</span>
                                    @elseif ($isService)
                                        {{ $content->title }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit($content->comment, 120) }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 shrink-0">
                                @foreach ($report->categories ?? [] as $category)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">
                                        {{ __('ui.moderation.categories.'.$category) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        @if ($report->reason)
                            <p class="mt-3 text-sm text-gray-700">
                                <span class="font-medium">{{ __('ui.moderation.reason') }} :</span>
                                {{ $report->reason }}
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            @if ($isService && $content !== null)
                                <a href="{{ route('services.show', $content) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    {{ __('ui.moderation.view_content') }}
                                </a>
                            @endif

                            <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST" class="ml-auto">
                                @csrf
                                <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    {{ __('ui.moderation.dismiss') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>{{ $reports->links() }}</div>
        @endif
    </div>
</x-app-layout>
