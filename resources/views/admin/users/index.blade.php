<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Gérer les utilisateurs" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-secondary-container/30 border border-outline-variant px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" value="Recherche" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" placeholder="Nom ou email" />
            </div>
            <div>
                <x-input-label for="role" value="Rôle" />
                <select id="role" name="role" class="mt-1 block w-full rounded-lg border-outline-variant shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\User::ROLE_CLIENT }}" @selected(request('role') === \App\Models\User::ROLE_CLIENT)>Client</option>
                    <option value="{{ \App\Models\User::ROLE_PROVIDER }}" @selected(request('role') === \App\Models\User::ROLE_PROVIDER)>Prestataire</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(request('role') === \App\Models\User::ROLE_ADMIN)>Administrateur</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-button-text font-semibold text-on-primary hover:bg-primary-container transition-colors">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($users->isEmpty())
            <x-empty-state icon="person_search" title="Aucun utilisateur trouvé." description="Aucun compte ne correspond à ces critères." />
        @else
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-outline-variant text-sm">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-on-surface-variant">Nom</th>
                            <th class="px-4 py-3 text-left font-medium text-on-surface-variant">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-on-surface-variant">Rôle</th>
                            <th class="px-4 py-3 text-left font-medium text-on-surface-variant">Statut</th>
                            <th class="px-4 py-3 text-left font-medium text-on-surface-variant">Inscrit le</th>
                            <th class="px-4 py-3 text-right font-medium text-on-surface-variant">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-on-surface">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-on-surface-variant">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-on-surface-variant capitalize">{{ $user->role }}</td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$user->status" />
                                </td>
                                <td class="px-4 py-3 text-on-surface-variant">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('suspend', $user)
                                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="inline" onsubmit="return confirm('Suspendre ce compte ?');">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-error hover:opacity-80">Suspendre</button>
                                        </form>
                                    @endcan
                                    @can('reactivate', $user)
                                        @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                            <form action="{{ route('admin.users.reactivate', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-secondary hover:opacity-80">Réactiver</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
