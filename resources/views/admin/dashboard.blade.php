@php
    $statusLabels = [
        'draft' => 'Brouillon', 'sent' => 'Envoyée', 'viewed' => 'Vue',
        'accepted' => 'Acceptée', 'refused' => 'Refusée', 'in_progress' => 'En cours',
        'completed' => 'Terminée', 'cancelled' => 'Annulée',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Administration</h2>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-on-surface">{{ $stats['clients'] }}</p>
                <p class="text-sm text-on-surface-variant">Clients</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-on-surface">{{ $stats['providers'] }}</p>
                <p class="text-sm text-on-surface-variant">Prestataires</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-error">{{ $stats['suspended_users'] }}</p>
                <p class="text-sm text-on-surface-variant">Comptes suspendus</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-on-surface">{{ $stats['services_active'] }}</p>
                <p class="text-sm text-on-surface-variant">Services actifs</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-on-surface">{{ $stats['services_total'] }}</p>
                <p class="text-sm text-on-surface-variant">Services au total</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-4">
                <p class="font-label-numeric text-2xl text-on-surface">{{ $stats['requests_total'] }}</p>
                <p class="text-sm text-on-surface-variant">Demandes au total</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6">
                <h3 class="font-headline-md text-headline-md text-on-surface mb-4">Demandes par statut</h3>
                <ul class="space-y-2">
                    @foreach ($statusLabels as $value => $label)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">{{ $label }}</span>
                            <span class="font-medium text-on-surface">{{ $requestsByStatus[$value] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Derniers utilisateurs inscrits</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary hover:text-primary-container">Voir tout →</a>
                </div>
                <ul class="divide-y divide-outline-variant">
                    @foreach ($recentUsers as $user)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <p class="font-medium text-on-surface">{{ $user->name }}</p>
                                <p class="text-on-surface-variant">{{ $user->email }}</p>
                            </div>
                            <span class="text-xs text-on-surface-variant">{{ $user->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                Gérer les utilisateurs
            </a>
            <a href="{{ route('admin.services.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                Gérer les services
            </a>
            <a href="{{ route('admin.categories.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                Gérer les catégories
            </a>
            <a href="{{ route('admin.plans.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                {{ __('ui.admin_plans.title') }}
            </a>
            <a href="{{ route('admin.moderation.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                {{ __('ui.moderation.title') }}
                @if (($stats['moderation_pending'] ?? 0) > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-tertiary px-2 py-0.5 text-xs font-bold text-white">{{ $stats['moderation_pending'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.verifications.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                @php $pendingCount = \App\Models\ProviderProfile::whereNotNull('id_card_path')->where('id_card_verified', false)->count(); @endphp
                Vérifications prestataires
                @if ($pendingCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-error px-2 py-0.5 text-xs font-bold text-on-error">{{ $pendingCount }}</span>
                @endif
            </a>
        </div>
    </div>
</x-app-layout>
