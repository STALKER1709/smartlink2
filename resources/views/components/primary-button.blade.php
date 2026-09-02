@props(['href' => null])

{{-- Le même bouton, quelle que soit la balise.

     Dix-sept des boutons primaires du dépôt étaient des liens : le composant ne
     rendant qu'un `<button>`, chacun avait été réécrit à la main, et chacun
     avait dérivé — rembourrage, palier de police, survol. Un lien qui mène
     ailleurs reste un `<a>` : c'est ce que le clavier et le lecteur d'écran
     attendent, et ce qu'un clic du milieu ouvre dans un onglet. --}}
@php
    $classes = 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary border border-transparent rounded-full font-button-text text-button-text text-on-primary hover:bg-primary-container active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-all duration-150';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
