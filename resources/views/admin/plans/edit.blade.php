<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ui.admin_plans.title') }} — {{ $plan->name() }}
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="{{ route('admin.plans.update', $plan) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-5">
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
                    <p class="mt-1 text-xs text-gray-500">{{ __('ui.admin_plans.unlimited_hint') }}</p>
                    <x-input-error :messages="$errors->get('max_services')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="max_monthly_requests" :value="__('ui.admin_plans.max_requests')" />
                    <x-text-input id="max_monthly_requests" name="max_monthly_requests" type="number" min="1"
                                  class="mt-1 block w-full" :value="old('max_monthly_requests', $plan->max_monthly_requests)" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('ui.admin_plans.unlimited_hint') }}</p>
                    <x-input-error :messages="$errors->get('max_monthly_requests')" class="mt-2" />
                </div>
            </div>

            <div class="space-y-2 border-t border-gray-100 pt-5">
                @foreach ([
                    'is_featured' => 'ui.admin_plans.is_featured',
                    'has_ai_writing' => 'ui.admin_plans.has_ai_writing',
                    'has_stats' => 'ui.admin_plans.has_stats',
                    'is_active' => 'ui.admin_plans.is_active',
                ] as $field => $label)
                    <label class="flex items-center gap-3 text-sm text-gray-800">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               @checked(old($field, $plan->$field))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-5">
                <a href="{{ route('admin.plans.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('ui.cancel') }}
                </a>
                <x-primary-button>{{ __('ui.save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
