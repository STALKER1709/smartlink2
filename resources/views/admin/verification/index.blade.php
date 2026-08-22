<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vérifications prestataires</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($pending->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
                Aucune vérification en attente.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($pending as $profile)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-col sm:flex-row gap-5">
                        {{-- Provider info --}}
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                @if ($profile->logo_path)
                                    <img src="{{ asset('storage/'.$profile->logo_path) }}" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 font-bold text-sm">
                                        {{ Str::substr($profile->business_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $profile->business_name }}</p>
                                    <p class="text-sm text-gray-500">{{ $profile->user->email }}</p>
                                </div>
                            </div>
                            @if ($profile->user->phone)
                                <p class="text-sm text-gray-600">Tél : {{ $profile->user->phone }}</p>
                            @endif
                            <p class="text-sm text-gray-600">
                                Ville : {{ $profile->city }}
                                @if ($profile->quarter) · {{ $profile->quarter }} @endif
                            </p>
                            <p class="text-xs text-gray-400">Soumis le {{ $profile->updated_at->format('d/m/Y H:i') }}</p>
                        </div>

                        {{-- ID Card preview --}}
                        <div class="sm:w-48">
                            <p class="text-xs font-medium text-gray-600 mb-1">Pièce d'identité</p>
                            @php $ext = pathinfo($profile->id_card_path, PATHINFO_EXTENSION); @endphp
                            @if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                                <a href="{{ asset('storage/'.$profile->id_card_path) }}" target="_blank">
                                    <img src="{{ asset('storage/'.$profile->id_card_path) }}" class="w-full h-28 object-cover rounded-md border border-gray-200 hover:opacity-80 transition">
                                </a>
                            @else
                                <a href="{{ asset('storage/'.$profile->id_card_path) }}" target="_blank"
                                   class="flex items-center gap-2 text-brand-600 hover:text-brand-800 text-sm mt-1">
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
                                <button type="submit" class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                    Approuver
                                </button>
                            </form>
                            <form action="{{ route('admin.verifications.reject', $profile) }}" method="POST" class="flex-1 sm:flex-none"
                                  onsubmit="return confirm('Rejeter ce document ? Le prestataire devra en soumettre un nouveau.')">
                                @csrf
                                <button type="submit" class="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                    Rejeter
                                </button>
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
