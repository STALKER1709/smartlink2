@props(['providerProfile', 'compact' => false])

@php
    // Le même partage que sur l'accueil : un visiteur vise la demande, un
    // prestataire de passage ne peut pas en envoyer et va donc à la fiche.
    $peutDemander = auth()->guest() || auth()->user()->can('create', \App\Models\ServiceRequest::class);
@endphp

@if ($compact)
    {{--
        La forme étroite : une rangée, pour la colonne latérale d'une fiche de
        service, où la carte pleine largeur ne tiendrait pas.
    --}}
    <a
        href="{{ route('providers.show', $providerProfile) }}"
        {{ $attributes->merge(['class' => 'group flex items-start gap-4 border-b border-outline-variant py-4 transition-colors duration-150 hover:bg-surface-container-low/70 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:gap-5 sm:py-5']) }}
    >
        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container-high sm:h-16 sm:w-16">
            @if ($providerProfile->logo_path)
                <img src="{{ media_url($providerProfile->logo_path) }}" alt="" loading="lazy"
                     class="h-full w-full object-cover" onerror="this.remove()">
            @else
                <span class="font-headline-md text-headline-md font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <p class="text-label-sm font-semibold uppercase tracking-wide text-primary">{{ $providerProfile->category?->name }}</p>
                <x-promoted-badge :profile="$providerProfile" />
            </div>

            {{-- Le nom passe à la ligne plutôt que de se couper : « Tchoumi
                 Électricité Bâtim… » ne désigne personne. --}}
            <h3 class="mt-1 flex items-start gap-1.5 font-headline-sm text-headline-sm leading-snug text-on-surface group-hover:text-primary">
                <span class="line-clamp-2">{{ $providerProfile->business_name }}</span>
                @if ($providerProfile->is_verified)
                    <x-icon name="verified" size="lg" filled label="{{ __('Prestataire vérifié') }}" class="mt-0.5 shrink-0 text-primary" />
                @endif
            </h3>

            <p class="mt-1 text-label-md text-on-surface-variant">
                {{ $providerProfile->city }}@if ($providerProfile->quarter), {{ $providerProfile->quarter }}@endif
                @isset($providerProfile->services_count)
                    <span aria-hidden="true" class="text-outline">·</span>
                    <span class="font-label-numeric">{{ $providerProfile->services_count }}</span>
                    {{ Str::plural('service', $providerProfile->services_count) }}
                @endisset
            </p>

            <div class="mt-2">
                @if ($providerProfile->rating_count)
                    <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" compact />
                @else
                    <span class="text-label-sm text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
                @endif
            </div>
        </div>
    </a>
@else
    {{--
        La carte de l'annuaire, reprise de la maquette : le texte à gauche, une
        vignette carrée de 192 px à droite, et les deux actions sous le texte.

        Elle est large et non en grille : un prestataire se compare sur sa zone
        d'intervention et sa note, deux lignes qui n'entrent pas dans une carte
        de 270 px. C'est aussi ce que fait la maquette, qui empile ces cartes
        sur toute la largeur.

        Pas de lien étendu à la carte entière ici : elle porte deux boutons,
        et un calque au-dessus les avalerait.
    --}}
    <article
        {{ $attributes->merge(['class' => 'flex flex-col gap-5 rounded-xl border border-outline-variant bg-surface-container-lowest p-5 shadow-elevation-1 transition-shadow duration-150 hover:shadow-elevation-2 md:flex-row-reverse md:items-stretch']) }}
    >
        <div class="relative h-40 w-full shrink-0 overflow-hidden rounded-lg bg-surface-container-high sm:h-48 md:w-48">
            <x-category-scene :name="$providerProfile->category?->name" :seed="$providerProfile->business_name" class="absolute inset-0" />

            <span class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border-2 border-surface-container-lowest bg-surface-container-high shadow-elevation-1">
                    @if ($providerProfile->logo_path)
                        <img src="{{ media_url($providerProfile->logo_path) }}" alt="" loading="lazy"
                             class="h-full w-full object-cover" onerror="this.remove()">
                    @else
                        <span class="font-headline-lg text-headline-lg font-bold text-primary">{{ Str::upper(Str::substr($providerProfile->business_name, 0, 1)) }}</span>
                    @endif
                </span>
            </span>
        </div>

        {{-- Sur un grand écran, les deux actions passent à droite du texte
             plutôt que sous lui : la carte fait alors 1 150 px, et empilées
             elles laissaient les deux tiers de la ligne vides. --}}
        <div class="flex min-w-0 flex-1 flex-col justify-between gap-5 lg:flex-row lg:items-center lg:gap-8">
            <div class="min-w-0 lg:flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($providerProfile->is_verified)
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2 py-0.5 font-label-sm text-label-sm font-bold uppercase tracking-wide text-primary">
                            <x-icon name="verified" size="xs" filled />
                            {{ __("Vérifié") }}
                        </span>
                    @endif
                    <x-promoted-badge :profile="$providerProfile" />
                </div>

                {{-- Le nom passe à la ligne plutôt que de se couper : « Tchoumi
                     Électricité Bâtim… » ne désigne personne. --}}
                <h3 class="mt-2 font-headline-md text-headline-md font-bold leading-tight text-on-surface">
                    <a href="{{ route('providers.show', $providerProfile) }}"
                       class="rounded-lg hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        {{ $providerProfile->business_name }}
                    </a>
                </h3>

                @if ($providerProfile->category)
                    <p class="mt-1 font-label-md text-label-md font-semibold text-primary">{{ $providerProfile->category->name }}</p>
                @endif

                <p class="mt-2 flex items-start gap-1 font-body-md text-body-md text-on-surface-variant">
                    <x-icon name="location_on" size="sm" class="mt-0.5 shrink-0" />
                    <span>
                        {{ __("Zone d'intervention :") }}
                        {{ $providerProfile->city }}@if ($providerProfile->quarter), {{ $providerProfile->quarter }}@endif
                        @isset($providerProfile->services_count)
                            <span aria-hidden="true" class="text-outline">·</span>
                            <span class="font-label-numeric">{{ $providerProfile->services_count }}</span>
                            {{ Str::plural('service', $providerProfile->services_count) }}
                        @endisset
                    </span>
                </p>

                <div class="mt-2">
                    @if ($providerProfile->rating_count)
                        <x-star-rating :rating="$providerProfile->rating_avg" :count="$providerProfile->rating_count" compact />
                    @else
                        <span class="font-label-sm text-label-sm text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
                    @endif
                </div>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                @if ($peutDemander)
                    <x-primary-button :href="route('requests.create', ['provider_id' => $providerProfile->user_id])">
                        <x-icon name="mail" size="sm" />
                        {{ __("Contacter") }}
                    </x-primary-button>
                @endif

                <x-secondary-button :href="route('providers.show', $providerProfile)">
                    {{ __("Profil complet") }}
                    <x-icon name="chevron_right" size="sm" />
                </x-secondary-button>
            </div>
        </div>
    </article>
@endif
