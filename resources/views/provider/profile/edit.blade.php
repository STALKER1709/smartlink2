<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mon profil prestataire</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('provider.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="business_name" value="Nom de l'entreprise / activité" />
                    <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full" :value="old('business_name', $providerProfile->business_name)" required />
                    <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="category_id" value="Catégorie principale" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Choisir une catégorie</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $providerProfile->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="description" value="Description (facultatif)" />
                    <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $providerProfile->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="city" value="Ville" />
                        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $providerProfile->city)" required />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="address" value="Adresse (facultatif)" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $providerProfile->address)" />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>
                </div>

                <!-- Service areas -->
                <div class="mt-4" x-data="{ areas: @js(old('service_areas', $providerProfile->service_areas ?: [''])) }">
                    <x-input-label value="Zones d'intervention (facultatif)" />
                    <template x-for="(area, index) in areas" :key="index">
                        <div class="flex gap-2 mt-2">
                            <input type="text" name="service_areas[]" x-model="areas[index]" maxlength="120" class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="button" @click="areas.splice(index, 1)" class="text-red-600 text-sm px-2">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="areas.push('')" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">
                        + Ajouter une zone
                    </button>
                    <x-input-error :messages="$errors->get('service_areas')" class="mt-2" />
                </div>

                <!-- Contact methods -->
                <div class="mt-4" x-data="{ contacts: @js(old('contact_methods', $providerProfile->contact_methods ?: [''])) }">
                    <x-input-label value="Moyens de contact (facultatif)" />
                    <template x-for="(contact, index) in contacts" :key="index">
                        <div class="flex gap-2 mt-2">
                            <input type="text" name="contact_methods[]" x-model="contacts[index]" maxlength="120" placeholder="ex: +237 6XX XXX XXX" class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="button" @click="contacts.splice(index, 1)" class="text-red-600 text-sm px-2">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="contacts.push('')" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">
                        + Ajouter un contact
                    </button>
                    <x-input-error :messages="$errors->get('contact_methods')" class="mt-2" />
                </div>

                <!-- Opening hours -->
                <div class="mt-4">
                    <p class="block font-medium text-sm text-gray-700 mb-2">Horaires d'ouverture (facultatif)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $day)
                            <div class="flex items-center gap-2">
                                <label for="opening_hours_{{ $day }}" class="w-24 text-sm text-gray-600 capitalize shrink-0">{{ $day }}</label>
                                <input
                                    id="opening_hours_{{ $day }}"
                                    type="text"
                                    name="opening_hours[{{ $day }}]"
                                    value="{{ old('opening_hours.'.$day, $providerProfile->opening_hours[$day] ?? '') }}"
                                    placeholder="ex: 8h-18h"
                                    class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Logo -->
                <div class="mt-4">
                    <x-input-label value="Logo (facultatif)" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($providerProfile->logo_path)
                            <img src="{{ asset('storage/'.$providerProfile->logo_path) }}" class="h-16 w-16 rounded-full object-cover">
                        @endif
                        <input type="file" name="logo" accept="image/*" class="text-sm text-gray-700">
                    </div>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
