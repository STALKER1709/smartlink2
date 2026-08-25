<x-guest-layout>
    <h1 class="font-headline-lg text-2xl font-bold text-on-surface">Créer votre compte</h1>
    <p class="mt-1 text-sm text-on-surface-variant">Gratuit pour les clients. 30 jours d'essai pour les prestataires.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="{ role: '{{ old('role', 'client') }}' }">
        @csrf

        {{-- Le choix du rôle en premier : il change la suite du formulaire, et
             c'est la question à laquelle le visiteur répond le plus vite. --}}
        <div>
            <x-input-label :value="__('Je suis un(e)...')" />
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label
                    class="flex cursor-pointer flex-col gap-1 rounded-xl border p-3 transition-colors"
                    :class="role === 'client' ? 'border-primary bg-primary-container/10 ring-1 ring-primary' : 'border-outline-variant hover:border-primary/40'"
                >
                    <input type="radio" name="role" value="client" x-model="role" class="sr-only">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">person</span>
                        <span class="font-semibold text-on-surface">{{ __('Client') }}</span>
                    </span>
                    <span class="text-xs text-on-surface-variant">Je cherche un prestataire</span>
                </label>

                <label
                    class="flex cursor-pointer flex-col gap-1 rounded-xl border p-3 transition-colors"
                    :class="role === 'provider' ? 'border-primary bg-primary-container/10 ring-1 ring-primary' : 'border-outline-variant hover:border-primary/40'"
                >
                    <input type="radio" name="role" value="provider" x-model="role" class="sr-only">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">handyman</span>
                        <span class="font-semibold text-on-surface">{{ __('Prestataire') }}</span>
                    </span>
                    <span class="text-xs text-on-surface-variant">Je propose mes services</span>
                </label>
            </div>
            <x-input-error :messages="$errors->get('role')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Nom complet')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div x-show="role === 'provider'" x-cloak>
            <x-input-label for="business_name" :value="__('Nom commercial / de l\'activité')" />
            <x-text-input id="business_name" class="mt-1 block w-full" type="text" name="business_name" :value="old('business_name')" autocomplete="organization" />
            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Téléphone')" />
            <x-text-input id="phone" class="mt-1 block w-full" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" placeholder="6XXXXXXXX" />
            <p class="mt-1 text-xs text-on-surface-variant">C'est par là que les prestataires vous joindront.</p>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="w-full">
            {{ __('Register') }}
        </x-primary-button>
    </form>

    <p class="mt-6 border-t border-outline-variant pt-5 text-center text-sm text-on-surface-variant">
        {{ __('Already registered?') }}
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:text-primary-container hover:underline">{{ __('Log in') }}</a>
    </p>
</x-guest-layout>
