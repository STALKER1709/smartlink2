<x-app-layout :titre="__('Nouvelle demande')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Nouvelle demande')" :back="route('requests.index')" :back-label="__('Mes demandes')" />
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
                        <p class="text-label-lg text-on-surface-variant">{{ $service->provider?->providerProfile?->business_name ?? $service->provider?->name }}</p>
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
                        <p class="text-label-lg text-on-surface-variant">{{ $provider->providerProfile?->category?->name }}</p>
                    </div>
                </div>
            @endif

            @if (! $service && ! $provider)
                {{-- Sans service ni prestataire, la demande ne peut aboutir :
                     la validation exige l'un des deux. Le formulaire était
                     tout de même offert, et le client qui l'envoyait recevait
                     « Le champ service est obligatoire lorsque provider id
                     n'est pas présent » — deux champs qu'il n'a jamais vus,
                     nommés comme des colonnes. On demande le destinataire
                     avant le message, pas l'inverse. --}}
                <p class="font-headline-md text-headline-md text-on-surface">{{ __("À qui adressez-vous cette demande ?") }}</p>
                <p class="prose-measure mt-2 text-body-md text-on-surface-variant">
                    {{ __("Une demande part toujours vers un prestataire précis. Choisissez d'abord le service qui vous intéresse, ou le prestataire à qui vous voulez écrire — le formulaire s'ouvrira avec son nom déjà rempli.") }}
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-5 py-2.5 font-button-text text-label-lg font-semibold text-on-primary transition-colors hover:bg-primary-container">
                        {{ __("Parcourir les services") }}
                    </a>
                    <a href="{{ route('providers.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-primary px-5 py-2.5 font-button-text text-label-lg font-semibold text-primary transition-colors hover:bg-primary-container/10">
                        {{ __("Parcourir les prestataires") }}
                    </a>
                </div>
            @else
            <form action="{{ route('requests.store') }}" method="POST" class="space-y-4">
                @csrf

                @if ($service)
                    <input type="hidden" name="service_id" value="{{ $service->id }}">
                @elseif ($provider)
                    <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                @endif

                <div>
                    <x-input-label for="message" :value="__('Votre message')" />
                    <textarea
                        id="message"
                        name="message"
                        rows="5"
                        required
                        maxlength="2000"
                        class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary"
                        placeholder="{{ __('Décrivez votre besoin, le lieu, et toute information utile au prestataire…') }}"
                    >{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="preferred_date" :value="__('Date souhaitée (facultatif)')" />
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

                {{-- Côte à côte à 390 px, les deux libellés français passaient
                     chacun sur deux lignes dans des boutons dimensionnés pour
                     l'anglais. Empilés, l'action principale en premier. --}}
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-end">
                    <x-secondary-button type="submit" name="action" value="draft" class="w-full sm:w-auto">
                        {{ __("Enregistrer comme brouillon") }}
                    </x-secondary-button>
                    <x-primary-button type="submit" name="action" value="send" class="w-full sm:w-auto">
                        {{ __("Envoyer la demande") }}
                    </x-primary-button>
                </div>
            </form>
            @endif
        </div>
    </div>
</x-app-layout>
