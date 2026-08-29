<x-app-layout :titre="__('Publier un service')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Publier un service')" :back="route('provider.services.index')" :back-label="__('Mes services')" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <form action="{{ route('provider.services.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @include('provider.services.form', ['service' => null, 'categories' => $categories])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-full bg-primary px-4 py-2 text-label-lg font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                        {{ __("Publier le service") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
