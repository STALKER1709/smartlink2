<x-app-layout :titre="__('Signaler un litige')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Déclarer un litige')"
                       :back="route('requests.show', $serviceRequest)"
                       :back-label="__('Retour à la demande')" />
    </x-slot>

    <div class="flex w-full justify-center px-margin-mobile py-8">
        <div class="w-full max-w-2xl rounded-xl border border-outline-variant bg-surface-container-lowest p-6 md:p-8">
            <div class="mb-8">
                <p class="font-body-lg text-body-lg text-on-surface-variant">
                    {{ __("Nous sommes désolés que votre expérience n'ait pas été satisfaisante. Décrivez le problème rencontré sur la demande") }}
                    <strong class="text-on-surface">« {{ $serviceRequest->service?->title ?? 'Demande directe' }} »</strong>
                    (n° <span class="font-label-numeric">{{ $serviceRequest->id }}</span>).
                    Notre équipe s'engage à vous répondre sous 24 heures.
                </p>
            </div>

            <form action="{{ route('disputes.store', $serviceRequest) }}" method="POST" enctype="multipart/form-data" class="space-y-6"
                  x-data="{ restant: 500 }">
                @csrf

                <div>
                    <label for="reason" class="mb-2 block font-body-md text-body-md text-on-surface">{{ __("Motif du litige") }}</label>
                    <div class="relative">
                        <select id="reason" name="reason" required
                                class="w-full appearance-none rounded-lg border border-outline-variant bg-surface-container-low py-3 pl-4 pr-10 font-body-md text-on-surface transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="" disabled @selected(! old('reason'))>{{ __("Sélectionnez un motif…") }}</option>
                            @foreach (\App\Models\Dispute::reasons() as $valeur => $libelle)
                                <option value="{{ $valeur }}" @selected(old('reason') === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-on-surface-variant">
                            <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>

                <div>
                    <label for="description" class="mb-2 block font-body-md text-body-md text-on-surface">{{ __("Description détaillée") }}</label>
                    <textarea id="description" name="description" rows="5" required maxlength="500"
                              x-on:input="restant = 500 - $event.target.value.length"
                              x-init="restant = 500 - $el.value.length"
                              placeholder="{{ __('Expliquez en détail le problème rencontré…') }}"
                              class="w-full resize-none rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 font-body-md text-on-surface transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description') }}</textarea>
                    <p class="mt-2 text-right font-label-numeric text-sm text-on-surface-variant">
                        <span x-text="500 - restant">0</span> {{ __('/ 500 caractères') }}
                    </p>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div>
                    <label class="mb-2 block font-body-md text-body-md text-on-surface">{{ __("Preuves visuelles (facultatif)") }}</label>
                    <p class="mb-3 font-body-md text-sm text-on-surface-variant">
                        {{ __("Ajoutez jusqu'à trois photos pour aider à trancher plus vite.") }}
                    </p>
                    <x-file-input name="evidence" accept="image/*" multiple
                                  :label="__('Ajouter des photos')"
                                  :hint="__('Aucune photo choisie.')" />
                    <x-input-error :messages="$errors->get('evidence')" class="mt-2" />
                    <x-input-error :messages="$errors->get('evidence.0')" class="mt-1" />
                </div>

                <div class="mt-8 border-t border-outline-variant pt-6">
                    {{-- L'avertissement avant le bouton, pas après : il doit
                         être lu pendant qu'on peut encore renoncer. --}}
                    <div class="mb-6 flex items-start gap-3 rounded-lg border border-error-container bg-error-container/20 p-4">
                        <span class="material-symbols-outlined mt-0.5 text-error" aria-hidden="true">info</span>
                        <p class="font-body-md text-sm text-on-surface">
                            {{ __("Les fausses déclarations peuvent entraîner la suspension de votre compte. Assurez-vous que toutes les informations fournies sont exactes.") }}
                        </p>
                    </div>

                    <div class="flex flex-col justify-end gap-4 sm:flex-row">
                        <a href="{{ route('requests.show', $serviceRequest) }}"
                           class="w-full rounded-full border border-primary px-6 py-3 text-center font-button-text text-button-text text-primary transition-colors hover:bg-surface-container-low sm:w-auto">
                            {{ __("Annuler") }}
                        </a>
                        <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container sm:w-auto">
                            {{ __("Soumettre le signalement") }}
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">send</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
