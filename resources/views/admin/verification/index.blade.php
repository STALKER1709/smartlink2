<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Vérifications prestataires</h2>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if ($pending->isEmpty())
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-8 text-center text-on-surface-variant">
                Aucune vérification en attente.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($pending as $profile)
                    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-5 flex flex-col sm:flex-row gap-5">
                        {{-- Provider info --}}
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                @if ($profile->logo_path)
                                    <img src="{{ media_url($profile->logo_path) }}" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-secondary-container/40 flex items-center justify-center text-on-secondary-container font-bold text-sm">
                                        {{ Str::substr($profile->business_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-on-surface">{{ $profile->business_name }}</p>
                                    <p class="text-sm text-on-surface-variant">{{ $profile->user->email }}</p>
                                </div>
                            </div>
                            @if ($profile->user->phone)
                                <p class="text-sm text-on-surface-variant">Tél : {{ $profile->user->phone }}</p>
                            @endif
                            <p class="text-sm text-on-surface-variant">
                                Ville : {{ $profile->city }}
                                @if ($profile->quarter) · {{ $profile->quarter }} @endif
                            </p>
                            <p class="text-xs text-on-surface-variant">Soumis le {{ $profile->updated_at->format('d/m/Y H:i') }}</p>
                        </div>

                        {{-- ID Card preview --}}
                        <div class="sm:w-48">
                            <p class="text-xs font-medium text-on-surface-variant mb-1">Pièce d'identité</p>
                            @php $ext = pathinfo($profile->id_card_path, PATHINFO_EXTENSION); @endphp
                            @if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                                <a href="{{ media_url($profile->id_card_path) }}" target="_blank">
                                    <img src="{{ media_url($profile->id_card_path) }}" class="w-full h-28 object-cover rounded-md border border-outline-variant hover:opacity-80 transition">
                                </a>
                            @else
                                <a href="{{ media_url($profile->id_card_path) }}" target="_blank"
                                   class="flex items-center gap-2 text-primary hover:text-primary-container text-sm mt-1">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Voir le PDF
                                </a>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex sm:flex-col gap-3 sm:justify-center sm:w-36">
                            <form action="{{ route('admin.verifications.approve', $profile) }}" method="POST" class="flex-1 sm:flex-none">
                                @csrf
                                <x-primary-button type="submit" class="w-full justify-center">
                                    Approuver
                                </x-primary-button>
                            </form>
                            <form action="{{ route('admin.verifications.reject', $profile) }}" method="POST" class="flex-1 sm:flex-none"
                                  onsubmit="return confirm('Rejeter ce document ? Le prestataire devra en soumettre un nouveau.')">
                                @csrf
                                <x-danger-button type="submit" class="w-full justify-center !bg-transparent !text-error border border-error hover:!bg-error-container">
                                    Rejeter
                                </x-danger-button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $pending->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
