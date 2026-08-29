<x-guest-layout>
    <div class="mb-4 text-sm text-on-surface-variant">
        {{ __('ui.phone_verification.description', ['phone' => Auth::user()->phone]) }}
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm font-medium text-secondary">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('phone.verify.check') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="code" :value="__('ui.phone_verification.code_label')" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                class="mt-1 block w-full text-center text-3xl tracking-widest font-bold"
                placeholder="_ _ _ _ _ _"
                autofocus
                required
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('ui.phone_verification.verify') }}
        </x-primary-button>
    </form>

    <div class="mt-4 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('phone.verify.send') }}">
            @csrf
            <button type="submit" class="text-primary hover:text-primary-container underline">
                {{ __('ui.phone_verification.resend') }}
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-on-surface-variant hover:text-on-surface">
                {{ __('ui.phone_verification.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
