<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon profil</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('client.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="first_name" value="Prénom" />
                        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $clientProfile->first_name)" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" value="Nom" />
                        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $clientProfile->last_name)" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="city" value="Ville (facultatif)" />
                        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $clientProfile->city)" />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="location" value="Quartier / lieu (facultatif)" />
                        <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $clientProfile->location)" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label value="Photo de profil (facultatif)" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($clientProfile->photo_path)
                            <img src="{{ asset('storage/'.$clientProfile->photo_path) }}" class="h-16 w-16 rounded-full object-cover">
                        @endif
                        <input type="file" name="photo" accept="image/*" class="text-sm text-gray-700">
                    </div>
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
