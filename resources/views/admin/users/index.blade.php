@php
    // Les rôles sont stockés en anglais ; ils ne doivent jamais paraître tels
    // quels. Le libellé est défini une fois et sert au filtre comme à la ligne.
    $roles = [
        \App\Models\User::ROLE_CLIENT => 'Client',
        \App\Models\User::ROLE_PROVIDER => 'Prestataire',
        \App\Models\User::ROLE_ADMIN => 'Administrateur',
    ];
@endphp

<x-app-layout :titre="__('Utilisateurs')" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Gérer les utilisateurs')"
                       :subtitle="$users->total().' '.Str::plural('compte', $users->total())" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-primary-container/15 border border-outline-variant px-4 py-3 text-label-md text-primary">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-elevation-1 p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" :value="__('Recherche')" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" :placeholder="__('Nom ou email')" />
            </div>
            <div>
                <x-input-label for="role" :value="__('Rôle')" />
                <select id="role" name="role" class="mt-1 block w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary">
                    <option value="">{{ __("Tous") }}</option>
                    @foreach ($roles as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('role') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-primary-button>
                    {{ __("Filtrer") }}
                </x-primary-button>
            </div>
        </form>

        @if ($users->isEmpty())
            <x-empty-state :title="__('Aucun utilisateur trouvé.')" :description="__('Aucun compte ne correspond à ces critères.')" />
        @else
            {{-- C'était un tableau à six colonnes qui débordait de l'écran :
                 « Rôle » se lisait « Pr… », les noms se coupaient en deux et
                 le statut n'était atteignable qu'en faisant défiler
                 latéralement. Une ligne empilée dit les mêmes six choses sans
                 rien couper. --}}
            <x-list-panel>
                @foreach ($users as $user)
                    {{-- L'action reste sur la ligne, jamais en dessous :
                         empilée, elle mettait un mot rouge entre deux noms,
                         cinquante fois de suite. --}}
                    <x-list-row class="flex items-start gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p class="font-medium text-on-surface">{{ $user->name }}</p>
                                {{-- La pastille ne paraît que pour l'exception :
                                     « Actif » répété sur chaque ligne n'apprend
                                     rien et noie le compte suspendu. --}}
                                @if ($user->status !== \App\Models\User::STATUS_ACTIVE)
                                    <x-status-badge :status="$user->status" />
                                @endif
                            </div>
                            <p class="mt-0.5 break-all text-label-md text-on-surface-variant">{{ $user->email }}</p>
                            <p class="mt-0.5 text-label-md text-on-surface-variant">
                                {{ $roles[$user->role] ?? $user->role }} ·
                                inscrit le <span class="font-label-numeric">{{ $user->created_at->format('d/m/Y') }}</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-4 pt-0.5">
                            @can('suspend', $user)
                                <form action="{{ route('admin.users.suspend', $user) }}" method="POST" onsubmit="return confirm('Suspendre le compte de {{ $user->name }} ?');">
                                    @csrf
                                    <button type="submit" class="text-label-md font-medium text-error hover:opacity-80">{{ __("Suspendre") }}</button>
                                </form>
                            @endcan
                            @can('reactivate', $user)
                                @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                    <form action="{{ route('admin.users.reactivate', $user) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-label-md font-medium text-secondary hover:opacity-80">{{ __("Réactiver") }}</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
