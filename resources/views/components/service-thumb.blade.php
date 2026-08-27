@props(['service', 'size' => 'text-3xl'])

@php
    $photoDistante = image_categorie($service->category?->name);
@endphp

{{--
    La vignette d'un service, et ses trois couches.

    Le repli est posé *sous* l'image, jamais dans un `@else` : une branche
    « pas d'image en base » ne se déclenche pas quand la ligne existe mais
    que le fichier a disparu. Le navigateur affichait alors sa propre icône
    d'image cassée — ce qui deviendra le cas courant dès que le stockage
    bascule sur S3 et qu'un objet manque. `onerror` retire l'image et
    découvre la couche du dessous.

    Trois couches, de la moins bonne à la meilleure : le pictogramme du
    métier, puis la photographie distante du métier quand `REMOTE_IMAGES`
    l'autorise, puis la photo déposée par le prestataire. Chacune s'efface si
    elle échoue, ce qui laisse toujours quelque chose à voir — et jamais le
    rectangle gris cassé du navigateur.

    `referrerpolicy` : l'hôte extérieur n'a pas à savoir de quelle page de
    SmartLink vient la requête.
--}}
<div {{ $attributes->merge(['class' => 'relative h-full w-full']) }}>
    <div class="absolute inset-0 flex items-center justify-center">
        <x-category-icon :icon="$service->category?->icon" class="{{ $size }} text-primary/30" />
    </div>

    @if ($photoDistante)
        <img src="{{ $photoDistante }}" alt="" loading="lazy" referrerpolicy="no-referrer"
             class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif

    @if ($service->images->isNotEmpty())
        <img src="{{ media_url($service->images->first()->path) }}" alt="" loading="lazy"
             class="relative h-full w-full object-cover" onerror="this.remove()">
    @endif
</div>
