<x-app-layout :titre="__('Modération')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.moderation.title')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        {{-- Le filet vert à gauche de l'intention : la modération signale,
             elle ne supprime pas, et cette phrase porte tout l'écran. --}}
        <p class="prose-measure mb-10 border-l-4 border-primary pl-4 font-body-lg text-body-lg text-on-surface-variant">
            {{ __('ui.moderation.intro') }}
        </p>

        @if ($reports->isEmpty())
            {{-- Le titre et la description disaient la même phrase deux fois.
                 La description dit maintenant ce qui se passera. --}}
            <x-empty-state :title="__('ui.moderation.empty')"
                           :description="__('Les services et les avis sont examinés à la publication ; ceux que l\'examen retient viendront ici.')" />
        @else
            {{-- Chaque signalement était une carte bordée posée sur du gris,
                 quinze fois de suite : aucun n'attirait l'œil plus que le
                 suivant. En rangées, le contenu signalé passe devant et le
                 motif du signalement le suit. --}}
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                @foreach ($reports as $report)
                    @php
                        $content = $report->moderatable;
                        $isService = $content instanceof \App\Models\Service;

                        // Une ligne dont les catégories ne sont pas un tableau
                        // — écriture manuelle, migration à moitié faite —
                        // faisait tomber toute la file de modération. C'est
                        // l'écran dont un administrateur a le plus besoin
                        // quand quelque chose cloche.
                        $categories = is_array($report->categories) ? $report->categories : [];
                    @endphp
                    <article class="group relative overflow-hidden rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest transition-colors hover:border-outline">
                        {{-- Le liseré en tête de carte : il signale sans crier,
                             là où un fond coloré ferait de chaque signalement
                             une alerte.

                             Il était en `tertiary-container`, qui portait
                             l'ambre avant la bascule sur la charte du drapeau
                             et porte le rouge depuis : chaque carte de la file
                             s'ouvrait donc sur un trait d'alerte, alors que
                             l'écran dit précisément le contraire au-dessus.
                             Le jaune de la palette tient ce rôle. --}}
                        <div class="absolute left-0 top-0 h-1 w-full bg-secondary-container" aria-hidden="true"></div>

                        <div class="p-6">
                            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-surface-container-high px-2 py-1 font-label-sm text-label-sm uppercase tracking-wider text-on-surface">
                                        {{ $isService ? __('ui.moderation.service') : __('ui.moderation.review') }}
                                    </span>
                                    <span class="font-label-numeric text-label-md text-on-surface-variant">{{ $report->created_at->translatedFormat('d M, H:i') }}</span>
                                </div>

                                @if ($categories !== [])
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @foreach ($categories as $category)
                                            @php $cle = 'ui.moderation.categories.'.$category; @endphp
                                            <span class="flex items-center gap-1 rounded-full bg-error-container px-3 py-1 font-label-numeric text-label-sm text-on-error-container">
                                                <x-icon name="warning" size="xs" />
                                                {{ Lang::has($cle) ? __($cle) : $category }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <p class="font-headline-sm text-headline-sm text-on-surface">
                                @if ($content === null)
                                    <span class="text-on-surface-variant">{{ __("— supprimé —") }}</span>
                                @elseif ($isService)
                                    {{ $content->title }}
                                @else
                                    {{ $content->comment }}
                                @endif
                            </p>

                            @if ($report->reason)
                                <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
                                    <span class="font-medium">{{ __('ui.moderation.reason') }} :</span>
                                    {{ $report->reason }}
                                </p>
                            @endif

                            <div class="mt-6 flex flex-wrap items-center gap-4 border-t border-outline-variant pt-4">
                                @if ($isService && $content !== null)
                                    <a href="{{ route('services.show', $content) }}" class="font-button-text text-button-text text-primary hover:text-primary-container">
                                        {{ __('ui.moderation.view_content') }}
                                    </a>
                                @endif

                                <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST" class="ml-auto">
                                    @csrf
                                    <button type="submit" class="rounded-full border border-outline px-4 py-2 font-button-text text-button-text text-on-surface-variant transition-colors hover:bg-surface-container-low">
                                        {{ __('ui.moderation.dismiss') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $reports->links() }}</div>
        @endif
    </div>
</x-app-layout>
