<x-app-layout>
    <!-- Hero -->
    <section class="bg-primary">
        <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-16 text-center">
            <h1 class="font-headline-xl text-headline-xl text-white">
                Trouvez le bon prestataire, près de chez vous
            </h1>
            <p class="mt-4 font-body-lg text-body-lg text-white/85 max-w-2xl mx-auto">
                SmartLink connecte les clients aux meilleurs prestataires de services au Cameroun.
            </p>

            <form action="{{ route('services.index') }}" method="GET" class="mt-8 max-w-xl mx-auto flex gap-2">
                <input
                    type="text"
                    name="term"
                    placeholder="Quel service recherchez-vous ?"
                    class="flex-1 rounded-lg border-0 px-4 py-2.5 text-on-surface placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-white"
                >
                <button type="submit" class="rounded-full bg-white px-5 py-2.5 font-button-text text-button-text text-primary hover:bg-surface-container-low transition-colors">
                    Rechercher
                </button>
            </form>
        </div>
    </section>

    <!-- Categories -->
    <section class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-12">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">Catégories populaires</h2>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach ($categories as $category)
                <a
                    href="{{ route('services.index', ['category_id' => $category->id]) }}"
                    class="flex flex-col items-center justify-center gap-2 bg-surface-container-lowest rounded-lg border border-outline-variant p-4 text-center hover:bg-surface-container-low hover:border-primary/40 transition-colors"
                >
                    <x-category-icon :icon="$category->icon" class="text-primary text-2xl" />
                    <span class="text-sm font-medium text-on-surface">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Recent services -->
    <section class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-12">
        <div class="flex items-center justify-between">
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Services récents</h2>
            <a href="{{ route('services.index') }}" class="text-sm font-semibold text-primary hover:text-primary-container flex items-center gap-1">
                Voir tout
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>

        @if ($recentServices->isEmpty())
            <p class="mt-6 text-on-surface-variant">Aucun service disponible pour le moment.</p>
        @else
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($recentServices as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>
        @endif
    </section>

    <!-- CTA -->
    @guest
        <section class="bg-inverse-surface">
            <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-12 text-center">
                <h2 class="font-headline-lg text-headline-lg text-inverse-on-surface">Vous êtes prestataire de services ?</h2>
                <p class="mt-2 font-body-md text-body-md text-inverse-on-surface/80">Rejoignez SmartLink et trouvez de nouveaux clients dès aujourd'hui.</p>
                <a href="{{ route('register') }}" class="mt-6 inline-flex rounded-full bg-primary px-6 py-3 font-button-text text-button-text text-on-primary hover:bg-primary-container transition-colors">
                    Créer un compte prestataire
                </a>
            </div>
        </section>
    @endguest
</x-app-layout>
