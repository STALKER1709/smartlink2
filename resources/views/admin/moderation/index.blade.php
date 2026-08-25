<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('ui.moderation.title')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8 space-y-4">
        <p class="text-sm text-on-surface-variant">{{ __('ui.moderation.intro') }}</p>

        @if ($reports->isEmpty())
            <p class="text-on-surface-variant">{{ __('ui.moderation.empty') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($reports as $report)
                    @php
                        $content = $report->moderatable;
                        $isService = $content instanceof \App\Models\Service;
                    @endphp
                    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs uppercase tracking-wide text-on-surface-variant">
                                    {{ $isService ? __('ui.moderation.service') : __('ui.moderation.review') }}
                                    · {{ __('ui.moderation.flagged_at') }} {{ $report->created_at->format('d/m/Y H:i') }}
                                </p>
                                <p class="mt-1 font-medium text-on-surface truncate">
                                    @if ($content === null)
                                        <span class="text-on-surface-variant">— supprimé —</span>
                                    @elseif ($isService)
                                        {{ $content->title }}
                                    @else
                                        {{ \Illuminate\Support\Str::limit($content->comment, 120) }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 shrink-0">
                                @foreach ($report->categories ?? [] as $category)
                                    <span class="rounded-full bg-tertiary-container/20 px-2.5 py-0.5 text-xs font-medium text-tertiary">
                                        {{ __('ui.moderation.categories.'.$category) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        @if ($report->reason)
                            <p class="mt-3 text-sm text-on-surface-variant">
                                <span class="font-medium">{{ __('ui.moderation.reason') }} :</span>
                                {{ $report->reason }}
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            @if ($isService && $content !== null)
                                <a href="{{ route('services.show', $content) }}" class="text-sm text-primary hover:text-primary-container">
                                    {{ __('ui.moderation.view_content') }}
                                </a>
                            @endif

                            <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST" class="ml-auto">
                                @csrf
                                <x-secondary-button type="submit">
                                    {{ __('ui.moderation.dismiss') }}
                                </x-secondary-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>{{ $reports->links() }}</div>
        @endif
    </div>
</x-app-layout>
