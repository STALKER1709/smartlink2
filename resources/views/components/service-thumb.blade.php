@props(['service', 'size' => 'text-3xl'])

@php
    $photoMetier = image_categorie($service->category?->name);
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
    métier, puis la photographie du métier quand `config/imagery.php` en
    déclare une, puis la photo déposée par le prestataire. Chacune s'efface si
    elle échoue, ce qui laisse toujours quelque chose à voir — et jamais le
    rectangle gris cassé du navigateur.

    `referrerpolicy` : quand la photo vient d'un hôte extérieur, il n'a pas à
    savoir de quelle page de SmartLink part la requête.
--}}
<div {{ $attributes->merge(['class' => 'relative h-full w-full']) }}>
    {{-- La couche du dessous : une scène dessinée, jamais un rectangle vide.
         C'était un pictogramme à 30 % d'opacité au milieu d'un aplat gris —
         ce qu'on voyait sur toute la page d'accueil dès qu'une photo
         manquait, c'est-à-dire tout le temps. --}}
    <x-category-scene :name="$service->category?->name" class="absolute inset-0" />

    @if ($photoMetier)
        <img src="{{ $photoMetier['url'] }}" alt="" loading="lazy" referrerpolicy="no-referrer"
             @if ($photoMetier['credit']) title="Photo : {{ $photoMetier['credit'] }}" @endif
             class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
    @endif

    @if ($service->images->isNotEmpty())
        <img src="{{ media_url($service->images->first()->path) }}" alt="" loading="lazy"
             class="relative h-full w-full object-cover" onerror="this.remove()">
    @endif
</div>
