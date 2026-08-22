<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">{{ __('ui.admin_plans.title') }}</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-margin-mobile md:px-margin-desktop py-8 space-y-4">
        <p class="text-sm text-on-surface-variant">{{ __('ui.admin_plans.intro') }}</p>

        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant divide-y divide-outline-variant overflow-hidden">
            @foreach ($plans as $plan)
                <div class="p-5 flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="font-headline-md text-headline-md text-on-surface">{{ $plan->name() }}</h3>
                            @unless ($plan->is_active)
                                <span class="rounded-full bg-surface-container-high px-2 py-0.5 text-xs font-medium text-on-surface-variant">
                                    {{ __('ui.admin_plans.inactive') }}
                                </span>
                            @endunless
                        </div>

                        <p class="mt-1 font-label-numeric text-2xl text-on-surface">{{ $plan->formattedPrice() }}</p>

                        <ul class="mt-2 text-sm text-on-surface-variant space-y-0.5">
                            <li>{{ __('ui.admin_plans.max_services') }} :
                                {{ $plan->allowsUnlimitedServices() ? __('ui.admin_plans.unlimited') : $plan->max_services }}</li>
                            <li>{{ __('ui.admin_plans.max_requests') }} :
                                {{ $plan->allowsUnlimitedRequests() ? __('ui.admin_plans.unlimited') : $plan->max_monthly_requests }}</li>
                            <li>{{ __('ui.admin_plans.subscribers') }} :
                                <span class="font-label-numeric font-medium">{{ $plan->active_subscriptions_count }}</span></li>
                        </ul>
                    </div>

                    <a href="{{ route('admin.plans.edit', $plan) }}" class="shrink-0 rounded-full border border-primary px-4 py-2 text-sm font-button-text font-semibold text-primary hover:bg-primary-container/10 transition-colors">
                        {{ __('ui.admin_plans.edit') }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
