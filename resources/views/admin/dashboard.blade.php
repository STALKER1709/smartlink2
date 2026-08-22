@php
    $statusLabels = [
        'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
        'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
        'completed' => 'Terminée', 'cancelled' => 'Annulée',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administration</h2>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['clients'] }}</p>
                <p class="text-sm text-gray-500">Clients</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['providers'] }}</p>
                <p class="text-sm text-gray-500">Prestataires</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-red-600">{{ $stats['suspended_users'] }}</p>
                <p class="text-sm text-gray-500">Comptes suspendus</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['services_active'] }}</p>
                <p class="text-sm text-gray-500">Services actifs</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['services_total'] }}</p>
                <p class="text-sm text-gray-500">Services au total</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['requests_total'] }}</p>
                <p class="text-sm text-gray-500">Demandes au total</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Demandes par statut</h3>
                <ul class="space-y-2">
                    @foreach ($statusLabels as $value => $label)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-medium text-gray-900">{{ $requestsByStatus[$value] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Derniers utilisateurs inscrits</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Voir tout →</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentUsers as $user)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-gray-500">{{ $user->email }}</p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $user->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Gérer les utilisateurs
            </a>
            <a href="{{ route('admin.services.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Gérer les services
            </a>
            <a href="{{ route('admin.categories.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Gérer les catégories
            </a>
            <a href="{{ route('admin.plans.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ __('ui.admin_plans.title') }}
            </a>
            <a href="{{ route('admin.moderation.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ __('ui.moderation.title') }}
                @if (($stats['moderation_pending'] ?? 0) > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $stats['moderation_pending'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.verifications.index') }}" class="rounded-md bg-white border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                @php $pendingCount = \App\Models\ProviderProfile::whereNotNull('id_card_path')->where('id_card_verified', false)->count(); @endphp
                Vérifications prestataires
                @if ($pendingCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ $pendingCount }}</span>
                @endif
            </a>
        </div>
    </div>
</x-app-layout>
