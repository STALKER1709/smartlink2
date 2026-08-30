@php
    // Les classes communes à toute cible de pagination. 44 px de côté : le
    // gabarit d'origine de Laravel en donnait 38, et ses couleurs étaient le
    // bleu et les gris de Tailwind — la seule zone de l'application à
    // n'appartenir à aucun jeton, présente sur toutes les listes.
    $cible = 'inline-flex h-11 min-w-11 items-center justify-center rounded-lg px-3 font-label-numeric text-label-numeric transition-colors';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination') }}" class="flex items-center justify-between gap-4">
        {{-- Sur un téléphone, on ne montre pas douze numéros : deux gestes,
             « précédent » et « suivant », et le rang courant entre les deux.
             Le gabarit d'origine cachait les numéros mais laissait deux boutons
             étirés sur toute la largeur, sans dire où l'on était. --}}
        <div class="flex w-full items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $cible }} cursor-default text-outline" aria-disabled="true">
                    <x-icon name="chevron_left" size="lg" :label="__('Page précédente')" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="{{ $cible }} border border-outline-variant text-on-surface hover:bg-surface-container">
                    <x-icon name="chevron_left" size="lg" :label="__('Page précédente')" />
                </a>
            @endif

            <span class="text-label-md text-on-surface-variant">
                {!! __('Page :courante sur :total', [
                    'courante' => '<span class="font-label-numeric text-on-surface">'.$paginator->currentPage().'</span>',
                    'total' => '<span class="font-label-numeric text-on-surface">'.$paginator->lastPage().'</span>',
                ]) !!}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="{{ $cible }} border border-outline-variant text-on-surface hover:bg-surface-container">
                    <x-icon name="chevron_right" size="lg" :label="__('Page suivante')" />
                </a>
            @else
                <span class="{{ $cible }} cursor-default text-outline" aria-disabled="true">
                    <x-icon name="chevron_right" size="lg" :label="__('Page suivante')" />
                </span>
            @endif
        </div>

        <div class="hidden w-full items-center justify-between gap-4 sm:flex">
            <p class="text-label-md text-on-surface-variant">
                {!! __('Affichage de :de à :a sur :total résultats', [
                    'de' => '<span class="font-label-numeric text-on-surface">'.$paginator->firstItem().'</span>',
                    'a' => '<span class="font-label-numeric text-on-surface">'.$paginator->lastItem().'</span>',
                    'total' => '<span class="font-label-numeric text-on-surface">'.$paginator->total().'</span>',
                ]) !!}
            </p>

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="{{ $cible }} cursor-default text-outline" aria-disabled="true">
                        <x-icon name="chevron_left" size="lg" :label="__('Page précédente')" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="{{ $cible }} text-on-surface-variant hover:bg-surface-container hover:text-on-surface">
                        <x-icon name="chevron_left" size="lg" :label="__('Page précédente')" />
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $cible }} cursor-default text-outline" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $cible }} bg-primary font-semibold text-on-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $cible }} text-on-surface-variant hover:bg-surface-container hover:text-on-surface"
                                   aria-label="{{ __('Aller à la page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="{{ $cible }} text-on-surface-variant hover:bg-surface-container hover:text-on-surface">
                        <x-icon name="chevron_right" size="lg" :label="__('Page suivante')" />
                    </a>
                @else
                    <span class="{{ $cible }} cursor-default text-outline" aria-disabled="true">
                        <x-icon name="chevron_right" size="lg" :label="__('Page suivante')" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
