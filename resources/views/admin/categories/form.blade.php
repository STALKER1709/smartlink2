<div>
    <x-input-label for="name" value="Nom de la catégorie" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category?->name)" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="icon" value="Icône (facultatif)" />
    <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full" :value="old('icon', $category?->icon)" placeholder="ex: wrench" />
    <x-input-error :messages="$errors->get('icon')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Description (facultatif)" />
    <textarea id="description" name="description" rows="3" maxlength="500" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">{{ old('description', $category?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $category?->is_active ?? true)) class="rounded border-outline-variant text-primary focus:ring-primary">
    <label for="is_active" class="text-sm text-on-surface-variant">Catégorie active</label>
    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
</div>
