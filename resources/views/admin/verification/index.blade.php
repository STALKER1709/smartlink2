<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Vérifications prestataires"
                       :subtitle="$pending->total().' '.Str::plural('pièce', $pending->total()).' en attente'" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($pending->isEmpty())
            <x-empty-state title="Aucune vérification en attente."
                           description="Les pièces d'identité déposées par les prestataires arrivent ici." />
        @else
            <x-list-panel>
                @foreach ($pending as $profile)
                    <x-list-row class="flex flex-col gap-5 sm:flex-row sm:items-start sm:gap-6">
                        {{-- Qui demande --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                @if ($profile->logo_path)
                                    <img src="{{ media_url($profile->logo_path) }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                @else
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-secondary-container/40 text-sm font-bold text-on-secondary-container">
                                        {{ Str::substr($profile->business_name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-medium text-on-surface">{{ $profile->business_name }}</p>
                                    <p class="break-all text-sm text-on-surface-variant">{{ $profile->user->email }}</p>
                                </div>
                            </div>

                            <p class="mt-2 text-sm text-on-surface-variant">
                                {{ $profile->city }}@if ($profile->quarter) · {{ $profile->quarter }} @endif
                                @if ($profile->user->phone)
                                    · <span class="font-label-numeric">{{ $profile->user->phone }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-sm text-on-surface-variant">
                                Soumis le <span class="font-label-numeric">{{ $profile->updated_at->format('d/m/Y H:i') }}</span>
                            </p>
                        </div>

                        {{-- La pièce déposée --}}
                        <div class="sm:w-44 sm:shrink-0">
                            @php
                                $ext = strtolower(pathinfo($profile->id_card_path, PATHINFO_EXTENSION));
                                // Le document ne vit plus sur une URL publique :
                                // il passe par une route qui vérifie la Policy.
                                $document = route('provider-profiles.id-document', $profile);
                            @endphp
                            @if (in_array($ext, ['jpg', 'jpeg', 'png']))
                                {{-- `object-cover` recadrait la pièce sur sa
                                     bande centrale : on ne voyait ni le type de
                                     document ni sa photo. Elle est montrée
                                     entière, quitte à laisser du fond autour. --}}
                                <a href="{{ $document }}" target="_blank" rel="noopener"
                                   class="block rounded-lg border border-outline-variant bg-surface-container p-1 transition-colors hover:border-primary">
                                    <img src="{{ $document }}" alt="Pièce d'identité déposée"
                                         class="h-28 w-full object-contain">
                                </a>
                            @else
                                <a href="{{ $document }}" target="_blank" rel="noopener"
                                   class="flex h-28 items-center justify-center gap-2 rounded-lg border border-outline-variant bg-surface-container text-sm font-medium text-primary transition-colors hover:border-primary">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">description</span>
                                    Ouvrir le PDF
                                </a>
                            @endif
                        </div>

                        {{-- La décision --}}
                        <div class="flex gap-3 sm:w-40 sm:shrink-0 sm:flex-col">
                            <form action="{{ route('admin.verifications.approve', $profile) }}" method="POST" class="flex-1">
                                @csrf
                                <x-primary-button type="submit" class="w-full">
                                    Approuver
                                </x-primary-button>
                            </form>
                            {{-- Le refus est une action pleine, pas un lien : il
                                 renvoie le prestataire au dépôt d'une nouvelle
                                 pièce. Bordé plutôt que plein, pour que
                                 l'approbation reste l'issue attendue. --}}
                            <form action="{{ route('admin.verifications.reject', $profile) }}" method="POST" class="flex-1"
                                  onsubmit="return confirm('Rejeter la pièce de {{ $profile->business_name }} ? Le prestataire devra en soumettre une nouvelle.')">
                                @csrf
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-error px-6 py-2.5 font-button-text text-button-text text-error transition-colors hover:bg-error-container focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2">
                                    Rejeter
                                </button>
                            </form>
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $pending->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
