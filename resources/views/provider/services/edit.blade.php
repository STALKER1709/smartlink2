<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier le service</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('provider.services.update', $service) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('provider.services.form', ['service' => $service, 'categories' => $categories])

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
