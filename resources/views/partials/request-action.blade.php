{{-- Le même appel à l'action tient à deux endroits sur les fiches — colonne
     latérale et barre mobile — et doit dire la même chose aux deux.

     Attend : $href, $label, et éventuellement $compact / $compactLabel / $icon. --}}
@php
    $compact = $compact ?? false;
    $icon = $icon ?? null;
    $classes = 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary font-button-text font-semibold text-on-primary transition-colors hover:bg-primary-container '
        .($compact ? 'px-5 py-2.5 text-sm' : 'px-4 py-3');
@endphp

@auth
    @can('create', \App\Models\ServiceRequest::class)
        <a href="{{ $href }}" class="{{ $classes }}">
            @if ($icon)<span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $icon }}</span>@endif
            {{ $compact ? ($compactLabel ?? $label) : $label }}
        </a>
    @else
        <p class="text-center text-sm text-on-surface-variant">{{ __("Seuls les clients peuvent envoyer une demande.") }}</p>
    @endcan
@else
    {{-- Le visiteur vise la demande, pas la connexion : le bouton porte donc
         l'action réelle et pointe vers elle. Le middleware `auth` mémorise
         l'intention et `redirect()->intended()` le ramène ici après connexion.
         Auparavant ce bouton menait droit à /login, et le service qu'il
         s'apprêtait à demander était perdu en route. --}}
    <a href="{{ $href }}" class="{{ $classes }}">
        @if ($icon)<span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $icon }}</span>@endif
        {{ $compact ? ($compactLabel ?? $label) : $label }}
    </a>

    @unless ($compact)
        <p class="mt-2 text-center text-xs text-on-surface-variant">
            {{ __("Connexion ou création de compte à l'étape suivante.") }}
            <span class="font-semibold text-primary">{{ __("Gratuit pour les clients.") }}</span>
        </p>
    @endunless
@endauth
