<x-app-layout>
    @php
        $profile = $service->provider->providerProfile;
        $prix = $service->price_amount
            ? number_format((float) $service->price_amount, 0, ',', ' ').' FCFA'
            : null;
    @endphp

    <div class="mx-auto max-w-container px-margin-mobile py-6 pb-28 md:px-margin-desktop md:py-8 lg:pb-8">
        <a href="{{ route('services.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary sm:hidden">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Tous les services
        </a>

        <nav class="mb-4 hidden items-center gap-1 text-sm text-on-surface-variant sm:flex">
            <a href="{{ route('services.index') }}" class="hover:text-primary">Services</a>
            <span class="material-symbols-outlined text-base">chevron_right</span>
            @if ($service->category)
                <a href="{{ route('services.index', ['category_id' => $service->category_id]) }}" class="hover:text-primary">{{ $service->category->name }}</a>
                <span class="material-symbols-outlined text-base">chevron_right</span>
            @endif
            <span class="truncate text-on-surface">{{ $service->title }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                {{-- Une image maîtresse plutôt qu'une mosaïque : c'est elle qui
                     donne envie, les autres ne sont que des compléments. --}}
                <div class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest">
                    <div class="aspect-[16/9] bg-secondary-container/40">
                        @if ($service->images->isNotEmpty())
                            <img src="{{ media_url($service->images->first()->path) }}" alt="{{ $service->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <x-category-icon :icon="$service->category?->icon" class="text-6xl text-primary/30" />
                            </div>
                        @endif
                    </div>

                    @if ($service->images->count() > 1)
                        <div class="grid grid-cols-4 gap-1 p-1 sm:grid-cols-6">
                            @foreach ($service->images->skip(1) as $image)
                                <img src="{{ media_url($image->path) }}" alt="" loading="lazy" class="aspect-square w-full rounded object-cover">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($service->category)
                            <span class="rounded-full bg-secondary-container/50 px-3 py-1 text-xs font-semibold text-on-secondary-container">{{ $service->category->name }}</span>
                        @endif
                        <x-status-badge :status="$service->is_available ? 'active' : 'inactive'" />
                        <x-promoted-badge :profile="$profile" />
                    </div>

                    <h1 class="mt-3 font-headline-lg text-headline-lg text-on-surface">{{ $service->title }}</h1>

                    @if ($service->city)
                        <p class="mt-2 flex items-center gap-1 text-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-base">location_on</span>
                            {{ $service->city }}@if ($service->quarter), {{ $service->quarter }}@endif
                            @if ($service->location) · {{ $service->location }} @endif
                        </p>
                    @endif

                    @if ($service->availability_note)
                        <p class="mt-4 flex items-start gap-2 rounded-xl border border-outline-variant bg-tertiary-container/15 px-4 py-3 text-sm text-on-surface">
                            <span class="material-symbols-outlined text-base text-tertiary">schedule</span>
                            {{ $service->availability_note }}
                        </p>
                    @endif

                    <h2 class="mt-8 font-headline-md text-headline-md text-on-surface">Description</h2>
                    <p class="mt-3 whitespace-pre-line leading-relaxed text-on-surface">{{ $service->description }}</p>
                </div>

                @if ($relatedServices->isNotEmpty())
                    <div class="mt-10">
                        <h2 class="font-headline-md text-headline-md text-on-surface">Services similaires</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-5">
                            @foreach ($relatedServices as $related)
                                <x-service-card :service="$related" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Colonne d'action, collante au défilement sur grand écran. --}}
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div class="hidden rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 lg:block">
                    <div class="font-label-numeric text-2xl text-on-surface">
                        @if ($prix)
                            {{ $prix }}
                            @if ($service->price_unit)
                                <span class="font-body-md text-base text-on-surface-variant">/ {{ $service->price_unit }}</span>
                            @endif
                        @else
                            <span class="font-body-md text-base text-on-surface-variant">Prix à convenir</span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-on-surface-variant">Prix indicatif — le montant se convient avec le prestataire.</p>

                    <div class="mt-4">
                        @include('partials.request-action', [
                            'href' => route('requests.create', ['service_id' => $service->id]),
                            'label' => 'Faire une demande',
                        ])
                    </div>
                </div>

                <div class="mt-0 lg:mt-4">
                    <x-provider-card :provider-profile="$profile" />
                </div>

                <p class="mt-4 flex items-start gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-xs leading-relaxed text-on-surface-variant">
                    <span class="material-symbols-outlined text-base text-primary">shield</span>
                    SmartLink ne prend aucune commission et ne perçoit aucun paiement : le règlement se convient directement avec le prestataire.
                </p>
            </aside>
        </div>
    </div>

    {{-- Sur mobile, l'action reste sous la main : sans cette barre, elle se
         trouvait tout en bas d'une page longue. --}}
    <div class="action-bar fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-container items-center gap-3">
            <div class="min-w-0 flex-1">
                <div class="truncate font-label-numeric text-base text-on-surface">
                    {{ $prix ?? 'Prix à convenir' }}
                    @if ($prix && $service->price_unit)
                        <span class="font-body-md text-xs text-on-surface-variant">/ {{ $service->price_unit }}</span>
                    @endif
                </div>
                <p class="truncate text-xs text-on-surface-variant">{{ $profile->business_name }}</p>
            </div>
            <div class="shrink-0">
                @include('partials.request-action', [
                    'href' => route('requests.create', ['service_id' => $service->id]),
                    'label' => 'Faire une demande',
                    'compactLabel' => 'Demander',
                    'compact' => true,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
