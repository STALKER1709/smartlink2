<x-guest-layout :titre="__('seo.login')" :indexable="false">
    <h1 class="font-headline-lg text-2xl font-bold text-on-surface">Content de vous revoir</h1>
    <p class="mt-1 text-sm text-on-surface-variant">Connectez-vous pour retrouver vos demandes et vos messages.</p>

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
                    <a class="text-sm text-primary hover:text-primary-container hover:underline" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-outline-variant text-primary focus:ring-primary">
            <span class="text-sm text-on-surface-variant">{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <p class="mt-6 border-t border-outline-variant pt-5 text-center text-sm text-on-surface-variant">
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="font-semibold text-primary hover:text-primary-container hover:underline">Créer un compte</a>
    </p>
</x-guest-layout>
