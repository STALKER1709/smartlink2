<x-app-layout>
    <!-- Hero -->
    <section class="bg-brand-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
            <h1 class="text-3xl sm:text-4xl font-bold text-white">
                Trouvez le bon prestataire, près de chez vous
            </h1>
            <p class="mt-4 text-brand-100 max-w-2xl mx-auto">
                SmartLink connecte les clients aux meilleurs prestataires de services au Cameroun.
            </p>

            <form action="{{ route('services.index') }}" method="GET" class="mt-8 max-w-xl mx-auto flex gap-2">
                <input
                    type="text"
                    name="term"
                    placeholder="Quel service recherchez-vous ?"
                    class="flex-1 rounded-md border-0 px-4 py-2.5 text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-white"
                >
                <button type="submit" class="rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-brand-600 hover:bg-brand-50">
                    Rechercher
                </button>
            </form>
        </div>
    </section>

    <!-- Categories -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-xl font-bold text-gray-900">Catégories populaires</h2>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach ($categories as $category)
                <a
                    href="{{ route('services.index', ['category_id' => $category->id]) }}"
                    class="flex flex-col items-center justify-center gap-2 bg-white rounded-lg border border-gray-200 p-4 text-center hover:shadow-md hover:border-brand-200 transition"
                >
                    <span class="text-2xl">{{ $category->icon ?? '🔧' }}</span>
                    <span class="text-sm font-medium text-gray-700">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- Recent services -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Services récents</h2>
            <a href="{{ route('services.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-800">
                Voir tout →
            </a>
        </div>

        @if ($recentServices->isEmpty())
            <p class="mt-6 text-gray-500">Aucun service disponible pour le moment.</p>
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
        <section class="bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
                <h2 class="text-2xl font-bold text-white">Vous êtes prestataire de services ?</h2>
                <p class="mt-2 text-gray-300">Rejoignez SmartLink et trouvez de nouveaux clients dès aujourd'hui.</p>
                <a href="{{ route('register') }}" class="mt-6 inline-flex rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Créer un compte prestataire
                </a>
            </div>
        </section>
    @endguest
</x-app-layout>
