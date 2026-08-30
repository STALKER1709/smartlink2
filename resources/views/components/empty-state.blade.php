@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

{{--
    Une liste vide sans explication laisse croire à une panne. Ce bloc dit ce
    qui manque et propose la sortie la plus probable.

    Il était centré dans un cadre en pointillés, sous une grande icône grise —
    le motif que produit n'importe quel générateur d'interface. Aligné à
    gauche, sans cadre, il se lit comme le reste de la page : c'est une phrase
    adressée au visiteur, pas un panneau d'absence. L'icône n'a pas seulement
    cessé d'être affichée : la propriété a disparu, pour qu'aucun appel ne
    croie encore la fournir.
--}}
<div {{ $attributes->merge(['class' => 'max-w-xl py-10']) }}>
    <p class="font-headline-lg text-headline-lg text-on-surface">{{ $title }}</p>

    @if ($description)
        <p class="mt-2 text-body-lg text-on-surface-variant">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-5 inline-flex items-center gap-2 font-button-text font-semibold text-primary hover:text-primary-container">
            {{ $actionLabel }}
            <x-icon name="arrow_forward" class="transition-transform group-hover:translate-x-0.5" />
        </a>
    @endif

    {{ $slot }}
</div>
