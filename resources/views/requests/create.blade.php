<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Nouvelle demande" :back="route('requests.index')" back-label="Mes demandes" />
    </x-slot>

    <div class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            @if ($service)
                <div class="flex items-center gap-3 mb-6 p-3 bg-surface-container-low rounded-lg border border-outline-variant">
                    <div class="h-12 w-12 rounded-lg bg-surface-container overflow-hidden shrink-0">
                        @if ($service->images->isNotEmpty())
                            <img src="{{ media_url($service->images->first()->path) }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-on-surface">{{ $service->title }}</p>
                        <p class="text-sm text-on-surface-variant">{{ $service->provider?->providerProfile?->business_name ?? $service->provider?->name }}</p>
                    </div>
                </div>
            @elseif ($provider)
                <div class="flex items-center gap-3 mb-6 p-3 bg-surface-container-low rounded-lg border border-outline-variant">
                    <div class="h-12 w-12 rounded-full bg-surface-container overflow-hidden shrink-0 flex items-center justify-center">
                        @if ($provider->providerProfile?->logo_path)
                            <img src="{{ media_url($provider->providerProfile->logo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="text-on-surface-variant font-semibold">{{ Str::substr($provider->providerProfile?->business_name ?? $provider->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-on-surface">{{ $provider->providerProfile?->business_name ?? $provider->name }}</p>
                        <p class="text-sm text-on-surface-variant">{{ $provider->providerProfile?->category?->name }}</p>
                    </div>
                </div>
            @else
                <p class="mb-6 text-sm text-on-surface-variant">
                    Décrivez votre besoin ci-dessous. Vous pouvez aussi démarrer une demande directement depuis la page d'un service ou d'un prestataire.
                </p>
            @endif

            <form action="{{ route('requests.store') }}" method="POST" class="space-y-4">
                @csrf

                @if ($service)
                    <input type="hidden" name="service_id" value="{{ $service->id }}">
                @elseif ($provider)
                    <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                @endif

                <div>
                    <x-input-label for="message" value="Votre message" />
                    <textarea
                        id="message"
                        name="message"
                        rows="5"
                        required
                        maxlength="2000"
                        class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary"
                        placeholder="Décrivez votre besoin, le lieu, et toute information utile au prestataire…"
                    >{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="preferred_date" value="Date souhaitée (facultatif)" />
                    <x-text-input
                        id="preferred_date"
                        name="preferred_date"
                        type="date"
                        class="mt-1 block w-full"
                        :value="old('preferred_date')"
                        min="{{ now()->format('Y-m-d') }}"
                    />
                    <x-input-error :messages="$errors->get('preferred_date')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-secondary-button type="submit" name="action" value="draft">
                        Enregistrer comme brouillon
                    </x-secondary-button>
                    <x-primary-button type="submit" name="action" value="send">
                        Envoyer la demande
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
