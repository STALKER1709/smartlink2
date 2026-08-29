@php
    /*
     * Les mentions que réclament les licences libres. Elles se déduisent de
     * `config/imagery.php` plutôt que d'être recopiées ici : une photo
     * remplacée sans que sa mention suive, et la page affirme le nom d'un
     * auteur dont l'image n'est plus là.
     *
     * Le commutateur n'entre pas en compte : une photo retirée de l'affichage
     * hier peut encore être en cache chez un visiteur, et la mention ne coûte
     * rien.
     */
    $photos = collect(config('imagery.categories', []))
        ->put('Bandeau prestataires', config('imagery.cta'))
        ->filter(fn ($entree) => is_array($entree) && ! empty($entree['url']))
        ->sortKeys();
@endphp

@if ($photos->isNotEmpty())
    <ul class="mt-4 space-y-2">
        @foreach ($photos as $intitule => $photo)
            <li class="flex flex-wrap items-baseline gap-x-2">
                <span class="font-medium text-on-surface">{{ $intitule }}</span>
                <span aria-hidden="true" class="text-outline">·</span>
                <span>{{ $photo['auteur'] ?? 'auteur non renseigné' }}</span>
                @if (! empty($photo['licence']))
                    <span aria-hidden="true" class="text-outline">·</span>
                    <span>{{ $photo['licence'] }}</span>
                @endif
                @if (! empty($photo['source']))
                    <a href="{{ $photo['source'] }}" target="_blank" rel="noopener noreferrer"
                       class="font-semibold text-primary">{{ __('voir la source') }}</a>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <p class="mt-4">
        {{ __("Les illustrations de métier affichées aujourd'hui sont dessinées pour SmartLink et lui appartiennent. Aucune photographie de tiers n'est employée.") }}
    </p>
@endif
