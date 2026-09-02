@props(['title', 'subtitle' => null, 'href' => null, 'linkLabel' => 'Voir tout'])

{{--
    Le titre d'une section, et le lien qui mène à sa liste complète.

    Sur mobile le lien passe **sous** le titre. Posé à côté, il tenait la
    ligne de base de la deuxième ligne d'un titre qui passe presque toujours
    à la ligne à 390 px — « Derniers services publiés » y prend deux lignes —
    et paraissait flotter. C'est la règle qu'applique déjà `x-page-header`.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between sm:gap-4']) }}>
    <div class="min-w-0">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-label-md text-on-surface-variant">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($href)
        <a href="{{ $href }}"
           class="group -mx-3 inline-flex min-h-11 shrink-0 items-center gap-1 rounded-full px-3 text-label-md font-semibold text-primary transition-colors hover:bg-primary-container/10 hover:text-primary-container">
            {{ $linkLabel }}
            <x-icon name="arrow_forward" size="sm" class="transition-transform duration-150 group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
