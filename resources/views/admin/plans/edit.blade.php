<x-app-layout :titre="__('Modifier le palier')" :indexable="false">
    <x-slot name="header">
        {{-- Le titre se compose en PHP, pas en interpolant du Blade dans un
             attribut : « __('…') }} — {{ $plan->name() » n'est pas une
             expression, et la page rendait une erreur 500. Aucun test ne
             passait par cet écran. --}}
        <x-page-header :title="__('ui.admin_plans.title').' — '.$plan->name()"
                       :back="route('admin.plans.index')" back-label="Formules" />
    </x-slot>

    <div class="max-w-xl mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        <form action="{{ route('admin.plans.update', $plan) }}" method="POST" class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="price_xaf" :value="__('ui.admin_plans.price')" />
                <x-text-input id="price_xaf" name="price_xaf" type="number" min="0" step="1"
                              class="mt-1 block w-full" :value="old('price_xaf', $plan->price_xaf)" required />
                <x-input-error :messages="$errors->get('price_xaf')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="max_services" :value="__('ui.admin_plans.max_services')" />
                    <x-text-input id="max_services" name="max_services" type="number" min="1"
                                  class="mt-1 block w-full" :value="old('max_services', $plan->max_services)" />
                    <p class="mt-1 text-label-sm text-on-surface-variant">{{ __('ui.admin_plans.unlimited_hint') }}</p>
                    <x-input-error :messages="$errors->get('max_services')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="max_monthly_requests" :value="__('ui.admin_plans.max_requests')" />
                    <x-text-input id="max_monthly_requests" name="max_monthly_requests" type="number" min="1"
                                  class="mt-1 block w-full" :value="old('max_monthly_requests', $plan->max_monthly_requests)" />
                    <p class="mt-1 text-label-sm text-on-surface-variant">{{ __('ui.admin_plans.unlimited_hint') }}</p>
                    <x-input-error :messages="$errors->get('max_monthly_requests')" class="mt-2" />
                </div>
            </div>

            <div class="space-y-2 border-t border-outline-variant pt-5">
                @foreach ([
                    'is_featured' => 'ui.admin_plans.is_featured',
                    'has_ai_writing' => 'ui.admin_plans.has_ai_writing',
                    'has_stats' => 'ui.admin_plans.has_stats',
                    'is_active' => 'ui.admin_plans.is_active',
                ] as $field => $label)
                    <label class="flex items-center gap-3 text-label-md text-on-surface">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               @checked(old($field, $plan->$field))
                               class="rounded border-outline-variant text-primary focus:ring-primary">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-outline-variant pt-5">
                <a href="{{ route('admin.plans.index') }}" class="inline-flex min-h-6 items-center text-label-md text-on-surface-variant hover:text-on-surface">
                    {{ __('ui.cancel') }}
                </a>
                <x-primary-button>{{ __('ui.save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
