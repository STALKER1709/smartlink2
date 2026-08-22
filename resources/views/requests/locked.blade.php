<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.nav.requests') }}</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-8 text-center">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ __('ui.subscription.quota_reached', ['count' => $plan?->max_monthly_requests ?? 0]) }}
            </h3>

            <p class="mt-3 text-sm text-gray-600">{{ __('ui.subscription.quota_hidden') }}</p>
            <p class="mt-2 text-sm text-gray-600">{{ __('ui.subscription.quota_upgrade') }}</p>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('provider.subscription.show') }}" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                    {{ __('ui.subscription.see_plans') }}
                </a>
                <a href="{{ route('requests.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('ui.subscription.back_to_requests') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
