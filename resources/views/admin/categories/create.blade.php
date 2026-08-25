<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Nouvelle catégorie" :back="route('admin.categories.index')" back-label="Catégories" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6">
            <form action="{{ route('admin.categories.store') }}" method="POST">
                @csrf

                @include('admin.categories.form', ['category' => null])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                        Créer la catégorie
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
