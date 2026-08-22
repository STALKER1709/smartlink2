@if (app(\App\Services\Ai\ServiceDraftWriter::class)->isAvailableFor(auth()->user()))
    <div
        x-data="{
            notes: '',
            working: false,
            applied: false,
            error: '',
            propose() {
                if (! this.notes.trim() || this.working) return;

                this.working = true;
                this.applied = false;
                this.error = '';

                fetch('{{ route('provider.services.draft') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        notes: this.notes,
                        category_id: document.getElementById('category_id')?.value || null,
                        city: document.getElementById('city')?.value || null,
                    }),
                })
                    .then(async response => {
                        const data = await response.json();
                        if (! response.ok) throw new Error(data.message ?? @js(__('ui.draft.failed')));
                        return data;
                    })
                    .then(data => {
                        document.getElementById('title').value = data.title;
                        document.getElementById('description').value = data.description;
                        this.applied = true;
                    })
                    .catch(e => { this.error = e.message || @js(__('ui.draft.failed')); })
                    .finally(() => { this.working = false; });
            },
        }"
        class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
    >
        <label for="ai-notes" class="block text-sm font-medium text-indigo-900">{{ __('ui.draft.label') }}</label>
        <p class="mt-1 text-xs text-indigo-800">{{ __('ui.draft.hint') }}</p>

        <textarea
            id="ai-notes"
            x-model="notes"
            rows="2"
            maxlength="600"
            placeholder="{{ __('ui.draft.placeholder') }}"
            class="mt-3 block w-full rounded-md border-indigo-300 text-sm"
        ></textarea>

        <div class="mt-3 flex flex-wrap items-center gap-3">
            <button
                type="button"
                @click="propose"
                :disabled="working || ! notes.trim()"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="! working">{{ __('ui.draft.submit') }}</span>
                <span x-show="working" x-cloak>{{ __('ui.draft.working') }}</span>
            </button>

            <p x-show="applied" x-cloak class="text-sm text-green-700">{{ __('ui.draft.applied') }}</p>
            <p x-show="error" x-cloak x-text="error" class="text-sm text-red-700"></p>
        </div>
    </div>
@endif

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
        <select id="city" name="city" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            <option value="">— Choisir une ville —</option>
            @foreach (['Yaoundé','Douala','Bafoussam','Bamenda','Garoua','Maroua','Ngaoundéré','Bertoua','Kribi','Limbé','Buea','Ebolowa','Kumba','Nkongsamba','Edéa','Bafia'] as $ville)
                <option value="{{ $ville }}" @selected(old('city', $service?->city) === $ville)>{{ $ville }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="quarter" value="Quartier (facultatif)" />
        <x-text-input id="quarter" name="quarter" type="text" class="mt-1 block w-full" :value="old('quarter', $service?->quarter)" maxlength="120" placeholder="ex: Bastos, Akwa…" />
        <x-input-error :messages="$errors->get('quarter')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="location" value="Adresse précise (facultatif)" />
    <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $service?->location)" />
    <x-input-error :messages="$errors->get('location')" class="mt-2" />
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
