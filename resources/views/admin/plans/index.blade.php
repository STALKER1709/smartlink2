<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('ui.admin_plans.title') }}</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-4">
        <p class="text-sm text-gray-600">{{ __('ui.admin_plans.intro') }}</p>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 divide-y divide-gray-100">
            @foreach ($plans as $plan)
                <div class="p-5 flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-900">{{ $plan->name() }}</h3>
                            @unless ($plan->is_active)
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">
                                    {{ __('ui.admin_plans.inactive') }}
                                </span>
                            @endunless
                        </div>

                        <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $plan->formattedPrice() }}</p>

                        <ul class="mt-2 text-sm text-gray-600 space-y-0.5">
                            <li>{{ __('ui.admin_plans.max_services') }} :
                                {{ $plan->allowsUnlimitedServices() ? __('ui.admin_plans.unlimited') : $plan->max_services }}</li>
                            <li>{{ __('ui.admin_plans.max_requests') }} :
                                {{ $plan->allowsUnlimitedRequests() ? __('ui.admin_plans.unlimited') : $plan->max_monthly_requests }}</li>
                            <li>{{ __('ui.admin_plans.subscribers') }} :
                                <span class="font-medium tabular-nums">{{ $plan->active_subscriptions_count }}</span></li>
                        </ul>
                    </div>

                    <a href="{{ route('admin.plans.edit', $plan) }}" class="shrink-0 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ __('ui.admin_plans.edit') }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
