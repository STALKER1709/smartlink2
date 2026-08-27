@props(['providerProfile', 'flottant' => false, 'contour' => false])

@php
    // `relationLoaded` évite une requête par carte dans une liste : la vue qui
    // affiche beaucoup de prestataires charge `favorites` une fois.
    $enFavori = auth()->check()
        && (auth()->user()->relationLoaded('favorites')
            ? auth()->user()->favorites->contains($providerProfile->id)
            : auth()->user()->favorites()->whereKey($providerProfile->id)->exists());
@endphp

{{--
    Le cœur des maquettes. Un formulaire plutôt qu'un lien : mettre en favori
    change un état, et un GET ne doit pas le faire — un préchargeur de liens
    remplirait la liste tout seul.

    Un visiteur non connecté voit le cœur et arrive sur la connexion : le
    masquer laisserait croire que la fonction n'existe pas.
--}}
<form action="{{ auth()->check() ? route('favorites.toggle', $providerProfile) : route('login') }}"
      method="{{ auth()->check() ? 'POST' : 'GET' }}"
      {{ $attributes->merge(['class' => $flottant ? 'absolute right-4 top-4 z-10' : '']) }}>
    @if (auth()->check())
        @csrf
    @endif

    <button type="submit"
            class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container-lowest/90 text-on-surface-variant shadow-sm transition-colors hover:text-error focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary {{ $contour ? 'border border-outline-variant' : '' }}"
            aria-label="{{ $enFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
            title="{{ $enFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
        <span class="material-symbols-outlined {{ $enFavori ? 'text-error' : '' }}"
              @if ($enFavori) style="font-variation-settings: 'FILL' 1;" @endif
              aria-hidden="true">favorite</span>
    </button>
</form>
