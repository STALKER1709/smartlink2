@props(['title', 'intro' => null, 'sections' => [], 'description' => null])

@php
    $editeur = config('legal.editeur');
    /*
     * Le bandeau s'affiche dès qu'une mention obligatoire manque, pas
     * seulement quand elles manquent toutes. `filter()->isEmpty()` faisait
     * disparaître l'avertissement au premier champ rempli, alors que le RCCM
     * ou le siège pouvaient encore être vides — c'est-à-dire au moment précis
     * où on croit avoir terminé.
     */
    $manquantes = collect($editeur)->filter(fn ($valeur) => blank($valeur))->keys();
    $incomplet = $manquantes->isNotEmpty();
    $valide = (bool) config('legal.valide_juridiquement');
@endphp

{{-- Les pages légales sont publiques : elles gardent leur place dans l'index,
     et leur titre est déjà celui du document. --}}
<x-app-layout :titre="$title" :description="$description ?? $intro">
    <x-slot name="header">
        <x-page-header :title="$title" :subtitle="__('Dernière révision : :date', ['date' => config('legal.derniere_revision')])" />
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-tablet lg:px-margin-desktop">
        <div class="lg:flex lg:items-start lg:gap-12">

            {{-- Sommaire : un document légal se consulte par section, il ne se
                 lit pas d'un trait. Il reste dans le flux sur mobile, où une
                 colonne collante volerait la moitié de l'écran. --}}
            @if ($sections)
                <nav class="mb-8 shrink-0 rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-lowest p-5 lg:sticky lg:top-6 lg:mb-0 lg:w-64"
                     aria-label="{{ __('Sommaire') }}">
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ __("Sommaire") }}</p>
                    <ol class="mt-3 space-y-2">
                        @foreach ($sections as $ancre => $libelle)
                            <li>
                                <a href="#{{ $ancre }}" class="flex gap-2 text-label-md text-on-surface-variant hover:text-primary">
                                    <span class="font-label-numeric text-label-sm text-outline">{{ $loop->iteration }}</span>
                                    <span>{{ $libelle }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            <div class="min-w-0 flex-1">
                @unless ($valide)
                    {{-- Un document juridique qui n'a pas été relu doit le dire
                         là où on le lit, pas dans un dépôt Git. --}}
                    <div class="rounded-xl border border-tertiary/30 bg-tertiary-container/10 p-4">
                        <p class="flex gap-2 font-semibold text-tertiary">
                            <x-icon name="shield" class="shrink-0" />
                            {{ __("Document non validé juridiquement") }}
                        </p>
                        <p class="mt-1 text-label-md text-on-surface-variant">
                            {{ __("Ce texte a été rédigé d'après le fonctionnement réel de la plateforme, mais il n'a pas été relu par un juriste. Il ne doit pas être considéré comme un engagement contractuel opposable en l'état.") }}
                        </p>
                    </div>
                @endunless

                @if ($incomplet)
                    <div class="mt-4 rounded-xl border border-error/30 bg-error-container/40 p-4">
                        <p class="font-semibold text-on-error-container">{{ __("Identité de l'éditeur non renseignée") }}</p>
                        <p class="mt-1 text-label-md text-on-error-container/90">
                            {!! __('Raison sociale, RCCM, siège social et contact sont à poser dans les variables <span class="font-label-numeric">LEGAL_*</span> de l\'environnement. Ces informations figurent sur vos statuts : elles ne peuvent pas être devinées.') !!}
                        </p>
                    </div>
                @endif

                @if ($intro)
                    <p class="prose-measure mt-6 text-body-lg text-on-surface-variant">{{ $intro }}</p>
                @endif

                <div class="prose-measure mt-8 space-y-10">
                    {{ $slot }}
                </div>

                <div class="mt-12 border-t border-outline-variant pt-6">
                    <p class="text-label-md text-on-surface-variant">
                        Une question sur ce document ?
                        @if ($editeur['email'])
                            Écrivez à <a href="mailto:{{ $editeur['email'] }}" class="font-semibold text-primary">{{ $editeur['email'] }}</a>.
                        @else
                            L'adresse de contact reste à renseigner.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
