@php
    $statusLabels = [
        'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
        'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
        'completed' => 'Terminée', 'cancelled' => 'Annulée',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Demande #{{ $serviceRequest->id }}
            </h2>
            <x-status-badge :status="$serviceRequest->status" />
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-6">
                <!-- Details -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    @if ($serviceRequest->service)
                        <p class="text-sm text-gray-500">Service concerné</p>
                        <a href="{{ route('services.show', $serviceRequest->service) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $serviceRequest->service->title }}
                        </a>
                    @else
                        <p class="text-sm text-gray-500">Demande directe (sans service précis)</p>
                    @endif

                    <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Client</p>
                            <p class="font-medium text-gray-900">
                                {{ $serviceRequest->client?->clientProfile?->fullName() ?? $serviceRequest->client?->name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-500">Prestataire</p>
                            <p class="font-medium text-gray-900">
                                {{ $serviceRequest->provider?->providerProfile?->business_name ?? $serviceRequest->provider?->name }}
                            </p>
                        </div>
                        @if ($serviceRequest->preferred_date)
                            <div>
                                <p class="text-gray-500">Date souhaitée</p>
                                <p class="font-medium text-gray-900">{{ $serviceRequest->preferred_date->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-gray-500">Envoyée le</p>
                            <p class="font-medium text-gray-900">{{ $serviceRequest->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-gray-500 text-sm">Message</p>
                        <p class="mt-1 text-gray-800 whitespace-pre-line">{{ $serviceRequest->message }}</p>
                    </div>
                </div>

                <!-- Actions -->
                @php $canRespond = Auth::user()->can('respond', $serviceRequest); @endphp
                @if ($canRespond || Auth::user()->can('cancel', $serviceRequest))
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Actions</h3>

                        <div class="flex flex-wrap items-center gap-3">
                            @if ($canRespond && in_array($serviceRequest->status, ['sent', 'viewed'], true))
                                <form action="{{ route('requests.accept', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                                        Accepter
                                    </button>
                                </form>

                                <form action="{{ route('requests.refuse', $serviceRequest) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="reason" maxlength="500" placeholder="Motif (facultatif)" class="rounded-md border-gray-300 text-sm">
                                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                                        Refuser
                                    </button>
                                </form>
                            @endif

                            @if ($canRespond && $serviceRequest->status === 'accepted')
                                <form action="{{ route('requests.start', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                        Démarrer la prestation
                                    </button>
                                </form>
                            @endif

                            @if ($canRespond && $serviceRequest->status === 'in_progress')
                                <form action="{{ route('requests.complete', $serviceRequest) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                        Marquer comme terminée
                                    </button>
                                </form>
                            @endif

                            @can('cancel', $serviceRequest)
                                <form action="{{ route('requests.cancel', $serviceRequest) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="reason" maxlength="500" placeholder="Motif (facultatif)" class="rounded-md border-gray-300 text-sm">
                                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Annuler la demande
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endif

                <!-- Review -->
                @can('create', [\App\Models\Review::class, $serviceRequest])
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Laisser un avis</h3>
                        <form action="{{ route('requests.review', $serviceRequest) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="rating" value="Votre note" />
                                <select id="rating" name="rating" required class="mt-1 block rounded-md border-gray-300 text-sm">
                                    <option value="">Choisir une note</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                                <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="comment" value="Commentaire (facultatif)" />
                                <textarea id="comment" name="comment" rows="3" maxlength="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                            </div>
                            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Envoyer mon avis
                            </button>
                        </form>
                    </div>
                @endcan

                @if ($serviceRequest->review)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Votre avis</h3>
                        <x-star-rating :rating="$serviceRequest->review->rating" />
                        @if ($serviceRequest->review->comment)
                            <p class="mt-2 text-sm text-gray-700">{{ $serviceRequest->review->comment }}</p>
                        @endif
                    </div>
                @endif

                <!-- Status history -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Historique</h3>
                    <ol class="space-y-4">
                        @foreach ($serviceRequest->statusHistory as $history)
                            <li class="flex gap-3">
                                <div class="h-2 w-2 mt-1.5 rounded-full bg-indigo-600 shrink-0"></div>
                                <div>
                                    <p class="text-sm text-gray-900">
                                        @if ($history->from_status)
                                            {{ $statusLabels[$history->from_status] ?? $history->from_status }} →
                                        @endif
                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $history->created_at->format('d/m/Y H:i') }}
                                        @if ($history->changedBy)
                                            · {{ $history->changedBy->name }}
                                        @endif
                                    </p>
                                    @if ($history->note)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $history->note }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <!-- Conversation -->
            <div>
                <h3 class="font-semibold text-gray-900 mb-4">Conversation</h3>
                @if ($serviceRequest->conversation)
                    <x-message-thread :conversation="$serviceRequest->conversation" />
                @else
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 text-sm text-gray-500">
                        La conversation s'ouvrira automatiquement une fois la demande acceptée par le prestataire.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
