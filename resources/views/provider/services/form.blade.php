<div>
    <x-input-label for="title" value="Titre du service" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $service?->title)" required />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="category_id" value="Catégorie" />
    <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Choisir une catégorie</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $service?->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Description" />
    <textarea id="description" name="description" rows="5" required maxlength="3000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $service?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="price_amount" value="Prix en FCFA (facultatif)" />
        <x-text-input id="price_amount" name="price_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price_amount', $service?->price_amount)" />
        <x-input-error :messages="$errors->get('price_amount')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="price_unit" value="Unité (ex: /heure, /m², forfait)" />
        <x-text-input id="price_unit" name="price_unit" type="text" class="mt-1 block w-full" :value="old('price_unit', $service?->price_unit)" maxlength="50" />
        <x-input-error :messages="$errors->get('price_unit')" class="mt-2" />
    </div>
</div>

<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="city" value="Ville" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $service?->city)" required />
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="location" value="Quartier / lieu précis (facultatif)" />
        <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $service?->location)" />
        <x-input-error :messages="$errors->get('location')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="hidden" name="is_available" value="0">
        <input type="checkbox" name="is_available" value="1" class="rounded border-gray-300" @checked(old('is_available', $service?->is_available ?? true))>
        Disponible actuellement
    </label>
</div>

<div class="mt-4">
    <x-input-label for="availability_note" value="Note de disponibilité (facultatif)" />
    <x-text-input id="availability_note" name="availability_note" type="text" class="mt-1 block w-full" :value="old('availability_note', $service?->availability_note)" maxlength="255" />
    <x-input-error :messages="$errors->get('availability_note')" class="mt-2" />
</div>

@if ($service)
    <div class="mt-4">
        <x-input-label for="status" value="Statut" />
        <select id="status" name="status" required class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="active" @selected(old('status', $service->status) === 'active')>Actif</option>
            <option value="inactive" @selected(old('status', $service->status) === 'inactive')>Inactif</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    @if ($service->images->isNotEmpty())
        <div class="mt-4">
            <p class="block font-medium text-sm text-gray-700 mb-2">Images actuelles</p>
            <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                @foreach ($service->images as $image)
                    <label class="relative block cursor-pointer">
                        <img src="{{ asset('storage/'.$image->path) }}" class="h-20 w-full object-cover rounded-md">
                        <span class="absolute top-1 right-1 bg-white/90 rounded-full p-1">
                            <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="rounded border-gray-300">
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-400">Cochez les images à supprimer.</p>
        </div>
    @endif
@endif

<div class="mt-4">
    <x-input-label for="images" value="Ajouter des images (max 5, facultatif)" />
    <input id="images" name="images[]" type="file" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
    <x-input-error :messages="$errors->get('images')" class="mt-2" />
    <x-input-error :messages="$errors->get('images.0')" class="mt-1" />
</div>
