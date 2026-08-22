<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gérer les utilisateurs</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="term" value="Recherche" />
                <x-text-input id="term" name="term" type="text" class="mt-1 block w-full" :value="request('term')" placeholder="Nom ou email" />
            </div>
            <div>
                <x-input-label for="role" value="Rôle" />
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tous</option>
                    <option value="{{ \App\Models\User::ROLE_CLIENT }}" @selected(request('role') === \App\Models\User::ROLE_CLIENT)>Client</option>
                    <option value="{{ \App\Models\User::ROLE_PROVIDER }}" @selected(request('role') === \App\Models\User::ROLE_PROVIDER)>Prestataire</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(request('role') === \App\Models\User::ROLE_ADMIN)>Administrateur</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Filtrer
                </button>
            </div>
        </form>

        @if ($users->isEmpty())
            <p class="text-gray-500">Aucun utilisateur trouvé.</p>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nom</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Rôle</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Statut</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Inscrit le</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ $user->role }}</td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$user->status" />
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @can('suspend', $user)
                                        <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="inline" onsubmit="return confirm('Suspendre ce compte ?');">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">Suspendre</button>
                                        </form>
                                    @endcan
                                    @can('reactivate', $user)
                                        @if ($user->status === \App\Models\User::STATUS_SUSPENDED)
                                            <form action="{{ route('admin.users.reactivate', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-green-600 hover:text-green-800">Réactiver</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
