{{-- Le corps commun des pages d'erreur que l'application peut rendre
     normalement — 403, 404, 419, 429. Le routage a fonctionné et
     l'application va bien : ces pages sont des pages du site.

     Les 500 et 503 ne passent pas par ici. Quand on ne sait pas ce qui est
     cassé, une page ne peut dépendre de rien : voir errors/partials/autonome.

     Attend : $icone, $titre, $message, et le slot pour les sorties. --}}
@props(['icone', 'titre', 'message'])

<div class="text-center">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
        <x-icon :name="$icone" size="xl" />
    </span>

    <h1 class="mt-4 font-headline-md text-headline-md text-on-surface">{{ $titre }}</h1>

    <p class="mt-2 text-label-md text-on-surface-variant">{{ $message }}</p>
</div>

{{-- Une page de refus sans sortie est une impasse, et on y arrive presque
     toujours par un lien légitime. --}}
<div class="mt-6 space-y-3">
    {{ $slot }}
</div>
