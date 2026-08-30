@props(['rating' => 0, 'count' => null, 'compact' => false, 'afficherNote' => true])

@php
    $pleines = (int) round((float) $rating);

    // Virgule décimale : l'application est en français, « 4.8 » y est un
    // anglicisme que l'œil accroche.
    // Attention : ne pas nommer cette variable comme une propriété du
    // composant — l'affectation ici écraserait silencieusement la valeur
    // reçue, et la propriété n'aurait plus aucun effet.
    $note = number_format((float) $rating, 1, ',', ' ');

    /*
     * L'étoile prend le jaune de la palette, et non l'or de Google (#fbbc04)
     * qui traînait ici : une couleur importée se voit, et c'était la seule
     * teinte de la page à n'appartenir à aucun jeton.
     *
     * Le jaune, et non plus l'ambre : la note et un abonnement qui expire
     * portaient la même couleur tant que la palette n'en comptait qu'une de
     * chaude. La charte réserve désormais le jaune aux notes, le rouge aux
     * alertes.
     */
    $jaune = 'text-secondary-container';

    // Cinq ligatures « star » ne disent rien à un lecteur d'écran.
    $annonce = $note.' sur 5'.(is_null($count) ? '' : ', '.$count.' avis');
@endphp

@if ($compact)
    {{-- Sur une carte étroite, cinq étoiles et « (12 avis) » ne tiennent pas :
         une étoile pleine et la note suffisent à porter l'information. --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}
          role="img" aria-label="{{ $annonce }}">
        <x-icon name="star" size="xs" filled class="{{ $jaune }}" />
        <span class="font-label-numeric text-label-numeric text-on-surface">{{ $note }}</span>
        @if (! is_null($count))
            <span class="text-label-sm text-on-surface-variant">({{ $count }})</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}
          role="img" aria-label="{{ $annonce }}">
        <span class="flex {{ $jaune }}" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                <x-icon name="star" size="sm" />
            @endfor
        </span>
        {{-- `afficherNote` à faux quand la note est déjà écrite à côté :
             l'écran des avis affichait « 4,0 » en gros puis « 4,0 (2 avis) »
             juste après. Les deux à faux — cinq étoiles et rien d'autre —, la
             balise ne s'écrit pas du tout : vide, elle laissait quand même
             l'écart de la rangée après la dernière étoile. --}}
        @if ($afficherNote || ! is_null($count))
            <span class="font-label-numeric text-label-numeric text-on-surface-variant">
                @if ($afficherNote){{ $note }}@endif
                @if (! is_null($count))
                    ({{ $count }} avis)
                @endif
            </span>
        @endif
    </span>
@endif
