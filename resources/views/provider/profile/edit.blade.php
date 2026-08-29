<x-app-layout :titre="__('Mon profil prestataire')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('ui.provider.my_profile')" />
    </x-slot>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <form action="{{ route('provider.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="business_name" :value="__('Nom de l\'entreprise / activité')" />
                    <x-text-input id="business_name" name="business_name" type="text" class="mt-1 block w-full" :value="old('business_name', $providerProfile->business_name)" required />
                    <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="category_id" :value="__('Catégorie principale')" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">{{ __("Choisir une catégorie") }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $providerProfile->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="description" :value="__('Description (facultatif)')" />
                    <textarea id="description" name="description" rows="4" maxlength="2000" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">{{ old('description', $providerProfile->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="city" :value="__('ui.provider.city')" />
                        <select id="city" name="city" required class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary text-sm">
                            <option value="">— {{ __('ui.provider.choose_city') }} —</option>
                            @foreach (['Yaoundé','Douala','Bafoussam','Bamenda','Garoua','Maroua','Ngaoundéré','Bertoua','Kribi','Limbé','Buea','Ebolowa','Kumba','Nkongsamba','Edéa','Bafia'] as $ville)
                                <option value="{{ $ville }}" @selected(old('city', $providerProfile->city) === $ville)>{{ $ville }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="quarter" :value="__('ui.provider.quarter')" />
                        <x-text-input id="quarter" name="quarter" type="text" class="mt-1 block w-full" :value="old('quarter', $providerProfile->quarter)" maxlength="120" placeholder="ex: Bastos, Akwa…" />
                        <x-input-error :messages="$errors->get('quarter')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="address" :value="__('ui.provider.address')" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $providerProfile->address)" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                {{-- WhatsApp --}}
                <div class="mt-4">
                    <x-input-label for="whatsapp" :value="__('ui.provider.whatsapp')" />
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center rounded-l-md border border-r-0 border-outline-variant bg-surface-container-low px-3 text-sm text-on-surface-variant">+237</span>
                        <x-text-input id="whatsapp" name="whatsapp" type="tel" class="block flex-1 rounded-none rounded-r-md" :value="old('whatsapp', $providerProfile->whatsapp)" placeholder="6XXXXXXXX" maxlength="20" />
                    </div>
                    <x-input-error :messages="$errors->get('whatsapp')" class="mt-2" />
                </div>

                {{-- Leaflet map for location --}}
                <div class="mt-4" x-data="{
                    lat: {{ old('latitude', $providerProfile->latitude ?? 3.848) }},
                    lng: {{ old('longitude', $providerProfile->longitude ?? 11.502) }},
                    map: null, marker: null,
                    init() {
                        this.map = L.map('provider-map').setView([this.lat, this.lng], 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap'
                        }).addTo(this.map);
                        this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
                        this.marker.on('dragend', (e) => {
                            this.lat = e.target.getLatLng().lat.toFixed(7);
                            this.lng = e.target.getLatLng().lng.toFixed(7);
                        });
                        this.map.on('click', (e) => {
                            this.lat = e.latlng.lat.toFixed(7);
                            this.lng = e.latlng.lng.toFixed(7);
                            this.marker.setLatLng([this.lat, this.lng]);
                        });
                    }
                }">
                    <x-input-label :value="__('ui.provider.location_map')" />
                    <p class="text-xs text-on-surface-variant mb-2">{{ __('ui.provider.location_hint') }}</p>
                    <div id="provider-map" class="w-full h-56 rounded-md border border-outline-variant z-0"></div>
                    <input type="hidden" name="latitude" :value="lat">
                    <input type="hidden" name="longitude" :value="lng">
                    {{-- « 3.848, 11.502 » nu ne dit rien à personne. La ligne
                         confirme d'abord que le repère est posé ; les
                         coordonnées suivent, à qui elles servent. --}}
                    <p class="mt-1 text-xs text-on-surface-variant">
                        {{ __("Repère placé ·") }} <span class="font-label-numeric" x-text="`${lat}, ${lng}`"></span>
                    </p>
                </div>

                {{-- ID Card upload --}}
                <div class="mt-6 border-t border-outline-variant pt-5">
                    <h3 class="font-medium text-on-surface mb-1">{{ __('ui.provider.id_card_title') }}</h3>
                    <p class="text-xs text-on-surface-variant mb-3">{{ __('ui.provider.id_card_hint') }}</p>

                    @if ($providerProfile->id_card_path)
                        <div class="mb-3 flex items-center gap-3">
                            @php
                                $ext = pathinfo($providerProfile->id_card_path, PATHINFO_EXTENSION);
                                // Passe par la route contrôlée : la pièce n'est
                                // plus joignable par une URL publique.
                                $document = route('provider-profiles.id-document', $providerProfile);
                            @endphp
                            @if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                                <img src="{{ $document }}" alt="{{ __('Votre pièce d\'identité déposée') }}" class="h-20 w-32 object-cover rounded border border-outline-variant">
                            @else
                                <a href="{{ $document }}" target="_blank" rel="noopener" class="text-primary text-sm hover:underline">{{ __("Voir le document") }}</a>
                            @endif
                            @if ($providerProfile->id_card_verified)
                                <span class="inline-flex items-center rounded-full bg-secondary-container/40 px-2.5 py-0.5 text-xs font-medium text-on-secondary-container">{{ __("Vérifié") }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-tertiary-container/20 px-2.5 py-0.5 text-xs font-medium text-tertiary">{{ __("En attente de vérification") }}</span>
                            @endif
                        </div>
                    @endif

                    <x-file-input name="id_card" accept="image/*,.pdf" :label="__('Déposer ma pièce')"
                                  :hint="__('Photo de la CNI ou du passeport, ou PDF.')" class="mt-2" />
                    <x-input-error :messages="$errors->get('id_card')" class="mt-2" />
                </div>

                <div class="mt-6 border-t border-outline-variant pt-5">
                    <h3 class="font-medium text-on-surface">{{ __("Où et comment vous joindre") }}</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">{{ __("Ces informations paraissent sur votre fiche publique.") }}</p>
                </div>

                <!-- Service areas -->
                <div class="mt-4" x-data="{ areas: @js(old('service_areas', $providerProfile->service_areas ?: [''])) }">
                    <x-input-label :value="__('Zones d\'intervention (facultatif)')" />
                    <template x-for="(area, index) in areas" :key="index">
                        <div class="flex gap-2 mt-2">
                            <input type="text" name="service_areas[]" x-model="areas[index]" maxlength="120" class="flex-1 rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                            <button type="button" @click="areas.splice(index, 1)" class="text-error text-sm px-2">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="areas.push('')" class="mt-2 text-sm text-primary hover:text-primary-container">
                        {{ __("+ Ajouter une zone") }}
                    </button>
                    <x-input-error :messages="$errors->get('service_areas')" class="mt-2" />
                </div>

                <!-- Contact methods -->
                <div class="mt-4" x-data="{ contacts: @js(old('contact_methods', $providerProfile->contact_methods ?: [''])) }">
                    <x-input-label :value="__('Moyens de contact (facultatif)')" />
                    <template x-for="(contact, index) in contacts" :key="index">
                        <div class="flex gap-2 mt-2">
                            <input type="text" name="contact_methods[]" x-model="contacts[index]" maxlength="120" placeholder="ex: +237 6XX XXX XXX" class="flex-1 rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary">
                            <button type="button" @click="contacts.splice(index, 1)" class="text-error text-sm px-2">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="contacts.push('')" class="mt-2 text-sm text-primary hover:text-primary-container">
                        {{ __("+ Ajouter un contact") }}
                    </button>
                    <x-input-error :messages="$errors->get('contact_methods')" class="mt-2" />
                </div>

                <!-- Opening hours -->
                <div class="mt-4">
                    <p class="block font-medium text-sm text-on-surface-variant mb-2">{{ __("Horaires d'ouverture (facultatif)") }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $day)
                            <div class="flex items-center gap-2">
                                <label for="opening_hours_{{ $day }}" class="w-24 text-sm text-on-surface-variant capitalize shrink-0">{{ $day }}</label>
                                <input
                                    id="opening_hours_{{ $day }}"
                                    type="text"
                                    name="opening_hours[{{ $day }}]"
                                    value="{{ old('opening_hours.'.$day, $providerProfile->opening_hours[$day] ?? '') }}"
                                    placeholder="ex: 8h-18h"
                                    class="flex-1 rounded-lg border-outline-variant text-sm focus:border-primary focus:ring-primary"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Logo -->
                <div class="mt-6 border-t border-outline-variant pt-5">
                    <x-input-label :value="__('Logo (facultatif)')" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($providerProfile->logo_path)
                            <img src="{{ media_url($providerProfile->logo_path) }}"
                                 alt="{{ __('Logo actuel de :nom', ['nom' => $providerProfile->business_name]) }}"
                                 class="h-16 w-16 rounded-full object-cover">
                        @endif
                        <x-file-input name="logo" accept="image/*" :label="__('Choisir un logo')" />
                    </div>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div class="mt-8 flex justify-end border-t border-outline-variant pt-5">
                    <button type="submit" class="rounded-full bg-primary px-5 py-2.5 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
                        {{ __('ui.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</x-app-layout>
