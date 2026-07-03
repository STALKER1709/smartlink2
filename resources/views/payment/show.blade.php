<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ui.payment.title') }}
        </h2>
    </x-slot>

    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($existingDeposit)
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <svg class="mx-auto h-10 w-10 text-green-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-green-800 font-semibold text-lg">{{ __('ui.payment.already_paid') }}</p>
                <p class="text-green-700 text-sm mt-1">
                    {{ __('ui.payment.reference') }} : <strong>{{ $existingDeposit->internal_reference }}</strong>
                </p>
                <p class="text-green-700 text-sm">
                    {{ __('ui.payment.amount') }} : <strong>{{ $existingDeposit->formattedAmount() }}</strong>
                </p>
                <a href="{{ route('requests.show', $serviceRequest) }}" class="mt-4 inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('ui.payment.back_to_request') }}
                </a>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900">{{ __('ui.payment.deposit_for') }} #{{ $serviceRequest->id }}</h3>
                    @if ($serviceRequest->service)
                        <p class="text-sm text-gray-500 mt-1">{{ $serviceRequest->service->title }}</p>
                    @endif
                    <div class="mt-4 flex items-center justify-between bg-indigo-50 rounded-lg p-4">
                        <span class="text-sm font-medium text-indigo-800">{{ __('ui.payment.deposit_amount') }}</span>
                        <span class="text-2xl font-bold text-indigo-900">{{ number_format($depositAmount, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ __('ui.payment.deposit_note') }}</p>
                </div>

                <form action="{{ route('payments.initiate', $serviceRequest) }}" method="POST" class="space-y-5">
                    @csrf

                    {{-- Operator selector --}}
                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-2">{{ __('ui.payment.operator') }}</label>
                        <div class="grid grid-cols-2 gap-3" x-data="{ selected: '{{ old('operator', '') }}' }">
                            <label
                                class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                                :class="selected === 'mtn' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                            >
                                <input type="radio" name="operator" value="mtn" class="sr-only" x-model="selected" @change="selected = 'mtn'">
                                <div class="flex flex-col items-center w-full gap-2">
                                    <div class="h-10 w-10 rounded-full bg-yellow-400 flex items-center justify-center text-black font-bold text-xs">MTN</div>
                                    <span class="text-sm font-medium text-gray-900">MTN Mobile Money</span>
                                </div>
                                <span
                                    class="pointer-events-none absolute -inset-px rounded-lg border-2 transition"
                                    :class="selected === 'mtn' ? 'border-yellow-500' : 'border-transparent'"
                                    aria-hidden="true"
                                ></span>
                            </label>

                            <label
                                class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                                :class="selected === 'orange' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 bg-white hover:bg-gray-50'"
                            >
                                <input type="radio" name="operator" value="orange" class="sr-only" x-model="selected" @change="selected = 'orange'">
                                <div class="flex flex-col items-center w-full gap-2">
                                    <div class="h-10 w-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold text-xs">OM</div>
                                    <span class="text-sm font-medium text-gray-900">Orange Money</span>
                                </div>
                                <span
                                    class="pointer-events-none absolute -inset-px rounded-lg border-2 transition"
                                    :class="selected === 'orange' ? 'border-orange-500' : 'border-transparent'"
                                    aria-hidden="true"
                                ></span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('operator')" class="mt-2" />
                    </div>

                    {{-- Phone number --}}
                    <div>
                        <x-input-label for="phone" :value="__('ui.payment.phone')" />
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">+237</span>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                inputmode="numeric"
                                maxlength="9"
                                placeholder="6XXXXXXXX"
                                value="{{ old('phone', Auth::user()->phone ? ltrim(str_replace('+237', '', Auth::user()->phone), '237') : '') }}"
                                class="block flex-1 rounded-none rounded-r-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('ui.payment.phone_hint') }}</p>
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    @error('payment')
                        <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    {{-- Instructions --}}
                    <div class="rounded-md bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800 space-y-1">
                        <p class="font-semibold">{{ __('ui.payment.instructions_title') }}</p>
                        <ol class="list-decimal list-inside space-y-1 mt-2">
                            <li>{{ __('ui.payment.step1') }}</li>
                            <li>{{ __('ui.payment.step2') }}</li>
                            <li>{{ __('ui.payment.step3') }}</li>
                        </ol>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('requests.show', $serviceRequest) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('ui.cancel') }}
                        </a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ __('ui.payment.pay_now') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
