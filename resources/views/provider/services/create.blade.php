<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Publier un service" :back="route('provider.services.index')" back-label="Mes services" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6">
            <form action="{{ route('provider.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @include('provider.services.form', ['service' => null, 'categories' => $categories])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                        Publier le service
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
