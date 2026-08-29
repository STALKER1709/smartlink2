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
        <a href="{{ $href }}" class="flex shrink-0 items-center gap-1 text-label-md font-semibold text-primary hover:text-primary-container">
            {{ $linkLabel }}
            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
        </a>
    @endif
</div>
