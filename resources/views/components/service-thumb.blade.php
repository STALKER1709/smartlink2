@props(['service', 'size' => 'text-3xl'])

{{--
    La vignette d'un service, avec son repli.

    Le repli est posé *sous* l'image, jamais dans un `@else` : une branche
    « pas d'image en base » ne se déclenche pas quand la ligne existe mais
    que le fichier a disparu. Le navigateur affichait alors sa propre icône
    d'image cassée — ce qui deviendra le cas courant dès que le stockage
    bascule sur S3 et qu'un objet manque. `onerror` retire l'image et
    découvre l'icône du métier.

    La plupart des prestataires ne déposent de toute façon aucune photo :
    l'icône du métier vaut mieux qu'un rectangle gris.
--}}
<div {{ $attributes->merge(['class' => 'relative h-full w-full']) }}>
    <div class="absolute inset-0 flex items-center justify-center">
        <x-category-icon :icon="$service->category?->icon" class="{{ $size }} text-primary/30" />
    </div>

    @if ($service->images->isNotEmpty())
        <img src="{{ media_url($service->images->first()->path) }}" alt="" loading="lazy"
             class="relative h-full w-full object-cover" onerror="this.remove()">
    @endif
</div>
