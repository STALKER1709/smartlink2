<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('ui.moderation.title')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <p class="prose-measure mb-6 text-body-md text-on-surface-variant">{{ __('ui.moderation.intro') }}</p>

        @if ($reports->isEmpty())
            {{-- Le titre et la description disaient la même phrase deux fois.
                 La description dit maintenant ce qui se passera. --}}
            <x-empty-state :title="__('ui.moderation.empty')"
                           description="Les services et les avis sont examinés à la publication ; ceux que l'examen retient viendront ici." />
        @else
            {{-- Chaque signalement était une carte bordée posée sur du gris,
                 quinze fois de suite : aucun n'attirait l'œil plus que le
                 suivant. En rangées, le contenu signalé passe devant et le
                 motif du signalement le suit. --}}
            <x-admin-list>
                @foreach ($reports as $report)
                    @php
                        $content = $report->moderatable;
                        $isService = $content instanceof \App\Models\Service;
                    @endphp
                    <x-admin-row class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-6">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-on-surface-variant">
                                {{ $isService ? __('ui.moderation.service') : __('ui.moderation.review') }}
                                · {{ __('ui.moderation.flagged_at') }}
                                <span class="font-label-numeric">{{ $report->created_at->format('d/m/Y H:i') }}</span>
                            </p>

                            {{-- Le contenu signalé était tronqué à une ligne :
                                 sur un commentaire, c'est justement la fin de
                                 la phrase qui motive le signalement. --}}
                            <p class="mt-1 font-medium text-on-surface">
                                @if ($content === null)
                                    <span class="text-on-surface-variant">— supprimé —</span>
                                @elseif ($isService)
                                    {{ $content->title }}
                                @else
                                    {{ $content->comment }}
                                @endif
                            </p>

                            @if ($report->categories)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    {{-- Une catégorie inconnue du fichier de
                                         langue s'affichait telle quelle, clé
                                         comprise : « ui.moderation.categories.
                                         harassment » en toutes lettres sur
                                         l'écran de l'administrateur. --}}
                                    @foreach ($report->categories as $category)
                                        @php $cle = 'ui.moderation.categories.'.$category; @endphp
                                        <span class="rounded-full bg-tertiary-container/20 px-2.5 py-0.5 text-xs font-medium text-tertiary">
                                            {{ Lang::has($cle) ? __($cle) : $category }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($report->reason)
                                <p class="mt-2 text-sm text-on-surface-variant">
                                    <span class="font-medium">{{ __('ui.moderation.reason') }} :</span>
                                    {{ $report->reason }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-4 sm:shrink-0 sm:self-start">
                            @if ($isService && $content !== null)
                                <a href="{{ route('services.show', $content) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                    {{ __('ui.moderation.view_content') }}
                                </a>
                            @endif

                            <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST" class="ml-auto sm:ml-0">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-on-surface-variant hover:text-on-surface">
                                    {{ __('ui.moderation.dismiss') }}
                                </button>
                            </form>
                        </div>
                    </x-admin-row>
                @endforeach
            </x-admin-list>

            <div class="mt-6">{{ $reports->links() }}</div>
        @endif
    </div>
</x-app-layout>
