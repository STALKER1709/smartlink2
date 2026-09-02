<x-app-layout :titre="__('Mon profil client')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Mon profil')" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <form action="{{ route('client.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="first_name" :value="__('Prénom')" />
                        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $clientProfile->first_name)" required />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="last_name" :value="__('Nom')" />
                        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $clientProfile->last_name)" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="city" :value="__('Ville (facultatif)')" />
                        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $clientProfile->city)" />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="location" :value="__('Quartier / lieu (facultatif)')" />
                        <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $clientProfile->location)" />
                        <x-input-error :messages="$errors->get('location')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label :value="__('Photo de profil (facultatif)')" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($clientProfile->photo_path)
                            <img src="{{ media_url($clientProfile->photo_path) }}"
                                 alt="{{ __('Votre photo de profil actuelle') }}"
                                 class="h-16 w-16 rounded-full object-cover" onerror="this.remove()">
                        @endif
                        <x-file-input name="photo" accept="image/*" :label="__('Choisir une photo')" />
                    </div>
                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <x-primary-button>
                        {{ __("Enregistrer") }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
