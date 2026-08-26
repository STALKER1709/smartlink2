@props(['rating' => 0, 'count' => null, 'compact' => false])

@php
    $pleines = (int) round((float) $rating);

    // Virgule décimale : l'application est en français, « 4.8 » y est un
    // anglicisme que l'œil accroche.
    $note = number_format((float) $rating, 1, ',', ' ');

    /*
     * L'étoile prend l'ambre de la palette et non l'or de Google (#fbbc04) qui
     * traînait ici : dans un monde vert et crème, une couleur importée se voit,
     * et c'était la seule teinte de la page à n'appartenir à aucun jeton.
     */
    $ambre = 'text-tertiary-container';

    // Cinq ligatures « star » ne disent rien à un lecteur d'écran.
    $annonce = $note.' sur 5'.(is_null($count) ? '' : ', '.$count.' avis');
@endphp

@if ($compact)
    {{-- Sur une carte étroite, cinq étoiles et « (12 avis) » ne tiennent pas :
         une étoile pleine et la note suffisent à porter l'information. --}}
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }}
          role="img" aria-label="{{ $annonce }}">
        <span class="material-symbols-outlined text-[16px] {{ $ambre }}" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">star</span>
        <span class="font-label-numeric text-label-numeric text-on-surface">{{ $note }}</span>
        @if (! is_null($count))
            <span class="text-xs text-on-surface-variant">({{ $count }})</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}
          role="img" aria-label="{{ $annonce }}">
        <span class="flex {{ $ambre }}" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' {{ $i <= $pleines ? 1 : 0 }};">star</span>
            @endfor
        </span>
        <span class="font-label-numeric text-label-numeric text-on-surface-variant">
            {{ $note }}
            @if (! is_null($count))
                ({{ $count }} avis)
            @endif
        </span>
    </span>
@endif
