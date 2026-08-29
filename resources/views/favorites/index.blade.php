<x-app-layout :titre="__('Mes favoris')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Favoris')" :subtitle="__('Vos prestataires de confiance sauvegardés.')" />
    </x-slot>

    <div class="mx-auto w-full max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-outline-variant bg-secondary-container/30 px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($favorites->isEmpty())
            {{-- L'état vide des maquettes : le cœur brisé, la phrase, et la
                 sortie. C'est le seul écran où une grande icône se justifie —
                 il n'y a rien d'autre à regarder. --}}
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-symbols-outlined mb-4 text-6xl text-outline-variant" aria-hidden="true">heart_broken</span>
                <h2 class="mb-2 font-headline-md text-headline-md text-on-surface">{{ __("Vous n'avez pas encore de favoris.") }}</h2>
                <p class="mx-auto max-w-md font-body-md text-body-md text-on-surface-variant">
                    {{ __("Explorez les prestataires et appuyez sur le cœur pour les ajouter à cette liste.") }}
                </p>
                <a href="{{ route('providers.index') }}"
                   class="mt-6 flex items-center gap-2 rounded-full bg-primary px-8 py-3 font-button-text text-button-text text-on-primary transition-all duration-100 hover:bg-primary-container active:scale-95">
                    <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    {{ __("Trouver un prestataire") }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($favorites as $profil)
                    <div class="relative flex flex-col gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest p-4">
                        <x-favorite-button :provider-profile="$profil" flottant />

                        <a href="{{ route('providers.show', $profil) }}" class="group flex items-start gap-4 pr-12">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-secondary-container/40">
                                @if ($profil->logo_path)
                                    <img src="{{ media_url($profil->logo_path) }}" alt="" loading="lazy" class="h-full w-full object-cover" onerror="this.remove()">
                                @else
                                    <span class="font-headline-md text-lg font-bold text-primary">{{ Str::upper(Str::substr($profil->business_name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary">{{ $profil->category?->name }}</p>
                                <h3 class="mt-1 font-headline-md text-base font-semibold leading-snug text-on-surface group-hover:text-primary">
                                    {{ $profil->business_name }}
                                </h3>
                                <p class="mt-1 text-sm text-on-surface-variant">
                                    {{ $profil->city }}
                                    <span aria-hidden="true" class="text-outline">·</span>
                                    <span class="font-label-numeric">{{ $profil->services_count }}</span> {{ Str::plural('service', $profil->services_count) }}
                                </p>
                            </div>
                        </a>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-outline-variant pt-3">
                            @if ($profil->rating_count)
                                <x-star-rating :rating="$profil->rating_avg" :count="$profil->rating_count" compact />
                            @else
                                <span class="text-xs text-on-surface-variant">{{ __("Pas encore d'avis") }}</span>
                            @endif

                            <a href="{{ route('requests.create', ['provider_id' => $profil->user_id]) }}"
                               class="shrink-0 font-button-text text-button-text text-primary hover:text-primary-container">
                                {{ __("Contacter") }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $favorites->links() }}</div>
        @endif
    </div>
</x-app-layout>
