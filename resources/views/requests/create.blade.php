<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouvelle demande</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            @if ($service)
                <div class="flex items-center gap-3 mb-6 p-3 bg-gray-50 rounded-md border border-gray-200">
                    <div class="h-12 w-12 rounded-md bg-gray-100 overflow-hidden shrink-0">
                        @if ($service->images->isNotEmpty())
                            <img src="{{ asset('storage/'.$service->images->first()->path) }}" alt="" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $service->title }}</p>
                        <p class="text-sm text-gray-500">{{ $service->provider?->providerProfile?->business_name ?? $service->provider?->name }}</p>
                    </div>
                </div>
            @elseif ($provider)
                <div class="flex items-center gap-3 mb-6 p-3 bg-gray-50 rounded-md border border-gray-200">
                    <div class="h-12 w-12 rounded-full bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                        @if ($provider->providerProfile?->logo_path)
                            <img src="{{ asset('storage/'.$provider->providerProfile->logo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="text-gray-400 font-semibold">{{ Str::substr($provider->providerProfile?->business_name ?? $provider->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $provider->providerProfile?->business_name ?? $provider->name }}</p>
                        <p class="text-sm text-gray-500">{{ $provider->providerProfile?->category?->name }}</p>
                    </div>
                </div>
            @else
                <p class="mb-6 text-sm text-gray-500">
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
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    <button type="submit" name="action" value="draft" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Enregistrer comme brouillon
                    </button>
                    <button type="submit" name="action" value="send" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
