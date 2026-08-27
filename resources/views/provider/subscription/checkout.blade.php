<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('ui.subscription.checkout_title', ['plan' => $plan->name()])" :back="route('provider.subscription.show')" back-label="Mon abonnement" />
    </x-slot>

    <div class="mx-auto flex w-full max-w-md flex-col gap-8 px-margin-mobile py-8 md:px-margin-desktop">
        {{-- La carte de récapitulatif des maquettes : ce qu'on achète, pour
             combien de temps, et le total détaché par un filet. --}}
        <section class="flex flex-col gap-4 rounded-xl border border-outline-variant bg-surface p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="mb-1 font-headline-lg text-headline-lg text-on-surface">Abonnement {{ $plan->name() }}</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        {{ __('Accès complet pour :days jours', ['days' => config('subscription.cycle_days')]) }}
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-secondary-container p-2 text-on-secondary-container">
                    <span class="material-symbols-outlined" aria-hidden="true">verified</span>
                </span>
            </div>

            <div class="mt-2 flex items-center justify-between border-t border-outline-variant pt-4">
                <span class="font-body-lg text-body-lg text-on-surface">Total à payer</span>
                <span class="font-label-numeric text-xl text-primary">{{ $plan->formattedPrice() }}</span>
            </div>
        </section>

        <div class="-mx-margin-mobile border-y border-outline-variant bg-surface-container-lowest px-margin-mobile py-6 md:mx-0 md:rounded-xl md:border md:p-6">
            <p class="text-sm text-on-surface-variant">
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
                        @foreach (['mtn' => ['ui.payment.mtn', 'bg-yellow-400 text-black', 'MTN'], 'orange' => ['ui.payment.orange', 'bg-orange-500 text-white', 'Orange']] as $value => $meta)
                            <label class="relative flex flex-col items-center justify-center gap-2 h-28 rounded-xl border border-outline-variant p-4 cursor-pointer hover:bg-surface-container-low has-[:checked]:border-primary has-[:checked]:bg-primary-container/10 transition-colors">
                                <input type="radio" name="operator" value="{{ $value }}"
                                       @checked(old('operator') === $value)
                                       class="sr-only peer">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs shadow-inner {{ $meta[1] }}">{{ $meta[2] }}</div>
                                <span class="text-sm font-medium text-on-surface text-center">{{ __($meta[0]) }}</span>
                                <span class="material-symbols-outlined absolute top-2 right-2 text-primary text-lg opacity-0 peer-checked:opacity-100 transition-opacity" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('operator')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="phone" :value="__('ui.payment.phone')" />
                    <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                                  :value="old('phone', auth()->user()->phone)" required />
                    <p class="mt-1 text-xs text-on-surface-variant">{{ __('ui.payment.phone_hint') }}</p>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="rounded-lg bg-secondary-container/20 border border-outline-variant p-4 text-sm text-on-secondary-container space-y-2">
                    <p>{{ __('ui.subscription.checkout_intro') }}</p>
                    <p>{{ __('ui.subscription.no_auto_debit') }}</p>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <a href="{{ route('provider.subscription.show') }}" class="text-sm text-on-surface-variant hover:text-on-surface">
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
