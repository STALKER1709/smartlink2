<x-app-layout :titre="__('Catégories')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Catégories de services')"
                       :subtitle="__('Gérez la taxonomie des services proposés sur la plateforme.')">
            <x-slot name="action">
                <a href="{{ route('admin.categories.create') }}"
                   class="flex items-center gap-2 rounded-lg bg-primary px-[24px] py-3 font-button-text text-button-text text-on-primary shadow-sm transition-colors hover:bg-primary-container">
                    <span class="material-symbols-outlined" aria-hidden="true">add</span>
                    {{ __("Ajouter une catégorie") }}
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto w-full max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-outline-variant bg-secondary-container/30 px-4 py-3 text-label-lg text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach ([
                ['Total catégories', number_format($totalCategories, 0, ',', ' '), false],
                ['Prestataires actifs', number_format($prestatairesActifs, 0, ',', ' '), false],
                ['Prix moyen indicatif', number_format($prixMoyen, 0, ',', ' ').' FCFA', true],
            ] as [$libelle, $valeur, $chiffre])
                <div class="flex flex-col rounded-xl border border-outline-variant bg-surface-container-lowest p-6">
                    <span class="mb-2 font-body-md text-label-lg font-semibold uppercase tracking-wider text-on-surface-variant">{{ $libelle }}</span>
                    <span @class([
                        'font-bold text-primary',
                        'font-label-numeric text-headline-md' => $chiffre,
                        'font-headline-xl text-headline-xl' => ! $chiffre,
                    ])>{{ $valeur }}</span>
                </div>
            @endforeach
        </div>

        @if ($categories->isEmpty())
            <x-empty-state :title="__('Aucune catégorie pour le moment.')" :description="__('Les catégories structurent la recherche : sans elles, rien n\'est classable.')" />
        @else
            {{-- Le tableau de la maquette à partir de `md`. En dessous il
                 déborderait de l'écran : à 390 px, une rangée empilée dit les
                 mêmes choses sans rien couper. --}}
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                <div class="hidden grid-cols-12 gap-4 border-b border-outline-variant bg-surface-container-low px-6 py-4 font-body-md text-body-md font-semibold text-on-surface md:grid">
                    <div class="col-span-5">{{ __("Catégorie") }}</div>
                    <div class="col-span-2 text-right">{{ __("Services") }}</div>
                    <div class="col-span-2 text-right">{{ __("Actifs") }}</div>
                    <div class="col-span-3 text-right">{{ __("Actions") }}</div>
                </div>

                <div class="divide-y divide-outline-variant">
                    @foreach ($categories as $category)
                        <div class="flex flex-col gap-3 px-4 py-4 transition-colors hover:bg-surface-container-low md:grid md:grid-cols-12 md:items-center md:gap-4 md:px-6">
                            <div class="min-w-0 md:col-span-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="shrink-0 rounded-md bg-secondary-container p-1.5 text-on-secondary-container">
                                        <x-category-icon :icon="$category->icon" class="text-base" />
                                    </span>
                                    <p class="font-medium text-on-surface">{{ $category->name }}</p>
                                    @unless ($category->is_active)
                                        <x-status-badge status="inactive" />
                                    @endunless
                                </div>
                                @if ($category->description)
                                    <p class="mt-1 line-clamp-2 text-label-lg text-on-surface-variant">{{ $category->description }}</p>
                                @endif
                            </div>

                            <div class="md:col-span-2 md:text-right">
                                <span class="font-label-numeric text-on-surface">{{ $category->services_count }}</span>
                                <span class="text-label-lg text-on-surface-variant md:hidden">{{ Str::plural('service', $category->services_count) }}</span>
                            </div>

                            <div class="md:col-span-2 md:text-right">
                                <span class="font-label-numeric text-on-surface-variant">{{ $category->active_services_count }}</span>
                                <span class="text-label-lg text-on-surface-variant md:hidden">actifs</span>
                            </div>

                            <div class="flex items-center justify-end gap-4 md:col-span-3">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-label-lg font-medium text-primary hover:text-primary-container">{{ __("Modifier") }}</a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                      onsubmit="return confirm('Supprimer « {{ $category->name }} » ? Les services qui s\'y rattachent perdront leur catégorie.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-label-lg font-medium text-error hover:opacity-80">{{ __("Supprimer") }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6">{{ $categories->links() }}</div>
        @endif
    </div>
</x-app-layout>
