<x-guest-layout :titre="__('seo.login')" :indexable="false">
    <h1 class="font-headline-md text-headline-md text-on-surface">{{ __("Content de vous revoir") }}</h1>
    <p class="mt-1 text-label-md text-on-surface-variant">{{ __("Connectez-vous pour retrouver vos demandes et vos messages.") }}</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Email ou téléphone')" />
            <x-text-input id="login" class="mt-1 block w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-2">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="-mr-2 inline-flex min-h-11 items-center rounded-full px-2 text-label-md text-primary transition-colors hover:bg-primary-container/10 hover:text-primary-container" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-outline-variant text-primary focus:ring-primary">
            <span class="text-label-md text-on-surface-variant">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <p class="mt-6 border-t border-outline-variant pt-5 text-center text-label-md text-on-surface-variant">
        {{ __("Pas encore de compte ?") }}
        <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-full px-2 font-semibold text-primary transition-colors hover:bg-primary-container/10 hover:text-primary-container">{{ __("Créer un compte") }}</a>
    </p>
</x-guest-layout>
