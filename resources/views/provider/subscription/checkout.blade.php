<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ui.subscription.checkout_title', ['plan' => $plan->name()]) }}
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

            <div class="flex items-baseline justify-between border-b border-gray-100 pb-4">
                <div>
                    <p class="font-semibold text-gray-900">{{ $plan->name() }}</p>
                    <p class="text-sm text-gray-500">{{ $plan->tagline() }}</p>
                </div>
                <p class="text-2xl font-semibold text-gray-900">{{ $plan->formattedPrice() }}</p>
            </div>

            <p class="mt-4 text-sm text-gray-600">
                @if ($subscription && $subscription->ends_at->isFuture())
                    {{ __('ui.subscription.extend_note', ['days' => config('subscription.cycle_days')]) }}
                @else
                    {{ __('ui.subscription.cycle_note', ['days' => config('subscription.cycle_days')]) }}
                @endif
            </p>

            <form action="{{ route('provider.subscription.subscribe', $plan) }}" method="POST" class="mt-6 space-y-5">
                @csrf

                <div>
                    <x-input-label :value="__('ui.payment.operator')" />
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        @foreach (['mtn' => 'ui.payment.mtn', 'orange' => 'ui.payment.orange'] as $value => $label)
                            <label class="flex items-center gap-3 rounded-md border border-gray-300 px-4 py-3 cursor-pointer hover:bg-gray-50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="operator" value="{{ $value }}"
                                       @checked(old('operator') === $value)
                                       class="text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-medium text-gray-800">{{ __($label) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('operator')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('ui.payment.phone')" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                                  :value="old('phone', auth()->user()->phone)" required />
                    <p class="mt-1 text-xs text-gray-500">{{ __('ui.payment.phone_hint') }}</p>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="rounded-md bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900 space-y-2">
                    <p>{{ __('ui.subscription.checkout_intro') }}</p>
                    <p class="text-blue-800">{{ __('ui.subscription.no_auto_debit') }}</p>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('provider.subscription.show') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('ui.cancel') }}
                    </a>
                    <x-primary-button>
                        {{ __('ui.subscription.pay_amount', ['amount' => $plan->formattedPrice()]) }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
