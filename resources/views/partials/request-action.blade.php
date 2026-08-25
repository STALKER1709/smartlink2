{{-- Le même appel à l'action tient à deux endroits sur les fiches — colonne
     latérale et barre mobile — et doit dire la même chose aux deux.

     Attend : $href, $label, et éventuellement $compact / $compactLabel. --}}
@php
    $compact = $compact ?? false;
    $classes = 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary font-button-text font-semibold text-on-primary transition-colors hover:bg-primary-container '
        .($compact ? 'px-5 py-2.5 text-sm' : 'px-4 py-3');
@endphp

@auth
    @can('create', \App\Models\ServiceRequest::class)
        <a href="{{ $href }}" class="{{ $classes }}">{{ $compact ? ($compactLabel ?? $label) : $label }}</a>
    @else
        <p class="text-center text-sm text-on-surface-variant">Seuls les clients peuvent envoyer une demande.</p>
    @endcan
@else
    <a href="{{ route('login') }}" class="{{ $classes }}">
        {{ $compact ? ($compactLabel ?? 'Se connecter') : 'Se connecter pour continuer' }}
    </a>
@endauth
