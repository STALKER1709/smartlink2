@php
    $statusLabels = \App\Support\RequestStatus::labels();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Administration" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        {{-- Chaque compteur mène là où l'on agit dessus. « Comptes suspendus »
             et « Demandes » n'ont pas d'écran filtré dédié : ils restent
             muets plutôt que de promettre un lien qui n'existe pas. --}}
        <x-stat-grid :cols="6">
            <x-stat-tile label="Clients" :value="$stats['clients']"
                         :href="route('admin.users.index', ['role' => \App\Models\User::ROLE_CLIENT])" />
            <x-stat-tile label="Prestataires" :value="$stats['providers']"
                         :href="route('admin.users.index', ['role' => \App\Models\User::ROLE_PROVIDER])" />
            <x-stat-tile label="Comptes suspendus" :value="$stats['suspended_users']" tone="error" />
            <x-stat-tile label="Services actifs" :value="$stats['services_active']" tone="primary"
                         :href="route('admin.services.index')" />
            <x-stat-tile label="Services au total" :value="$stats['services_total']"
                         :href="route('admin.services.index')" />
            <x-stat-tile label="Demandes au total" :value="$stats['requests_total']" />
        </x-stat-grid>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-3 border-b border-outline-variant pb-2">Demandes par statut</h3>
                <ul class="space-y-2">
                    @foreach ($statusLabels as $value => $label)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">{{ $label }}</span>
                            <span class="font-label-numeric font-medium text-on-surface">{{ $requestsByStatus[$value] ?? 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between border-b border-outline-variant pb-2">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Derniers utilisateurs inscrits</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary hover:text-primary-container">Voir tout →</a>
                </div>
                <ul class="divide-y divide-outline-variant">
                    @foreach ($recentUsers as $user)
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-on-surface">{{ $user->name }}</p>
                                <p class="truncate text-on-surface-variant">{{ $user->email }}</p>
                            </div>
                            <span class="font-label-numeric shrink-0 text-xs text-on-surface-variant">{{ $user->created_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Utilisateurs et services sont déjà atteignables depuis les
             chiffres du haut : les répéter ici en pastilles n'ajoutait qu'une
             seconde façon d'aller au même endroit. Ne restent que les écrans
             qu'aucun compteur ne porte. --}}
        <div class="mt-8 flex flex-wrap gap-3 border-t border-outline-variant pt-6">
            <a href="{{ route('admin.categories.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                Catégories
            </a>
            <a href="{{ route('admin.plans.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                {{ __('ui.admin_plans.title') }}
            </a>
            <a href="{{ route('admin.moderation.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                {{ __('ui.moderation.title') }}
                @if (($stats['moderation_pending'] ?? 0) > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-tertiary px-2 py-0.5 font-label-numeric text-xs font-bold text-white">{{ $stats['moderation_pending'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.verifications.index') }}" class="rounded-full bg-surface-container-lowest border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                @php $pendingCount = \App\Models\ProviderProfile::whereNotNull('id_card_path')->where('id_card_verified', false)->count(); @endphp
                Vérifications
                @if ($pendingCount > 0)
                    <span class="ml-1 inline-flex items-center justify-center rounded-full bg-error px-2 py-0.5 font-label-numeric text-xs font-bold text-on-error">{{ $pendingCount }}</span>
                @endif
            </a>
        </div>
    </div>
</x-app-layout>
