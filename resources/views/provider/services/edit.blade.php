<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Modifier le service" :back="route('provider.services.index')" back-label="Mes services" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <form action="{{ route('provider.services.update', $service) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('provider.services.form', ['service' => $service, 'categories' => $categories])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
