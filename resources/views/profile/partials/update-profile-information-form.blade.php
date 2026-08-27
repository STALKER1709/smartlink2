@php
    // Le compte n'a pas de photo à lui : elle appartient au profil du rôle —
    // logo pour un prestataire, portrait pour un client. La carte montre celle
    // qui existe et renvoie là où elle se change, plutôt que d'ouvrir un second
    // endroit où déposer une image.
    $photo = $user->isProvider()
        ? $user->providerProfile?->logo_path
        : $user->clientProfile?->photo_path;

    $lienPhoto = match (true) {
        $user->isProvider() => route('provider.profile.edit'),
        $user->isClient() => route('client.profile.edit'),
        default => null,
    };
@endphp

<x-settings-card :title="__('Profile Information')" icon="person">
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- `max-w-3xl` : la carte occupe toute la largeur, le formulaire non.
         Étirés sur les 1 100 px d'un écran de bureau, « Adresse e-mail » et
         « Nom » devenaient deux rubans de saisie où l'œil perd le rapport
         entre l'étiquette et son champ. --}}
    <form method="post" action="{{ route('profile.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('patch')

        <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
            <div class="relative shrink-0">
                <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-outline-variant bg-surface-container">
                    @if ($photo)
                        <img src="{{ media_url($photo) }}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                    @else
                        <span class="font-headline-lg text-2xl font-bold text-primary">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>

                @if ($lienPhoto)
                    <a href="{{ $lienPhoto }}"
                       class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full border-2 border-surface-container-lowest bg-primary text-on-primary transition-colors hover:bg-primary-container"
                       aria-label="{{ __('Change photo') }}" title="{{ __('Change photo') }}">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">edit</span>
                    </a>
                @endif
            </div>

            <div>
                <p class="font-medium text-on-surface">{{ __('Profile photo') }}</p>
                <p class="mt-0.5 text-sm text-on-surface-variant">{{ __('A square image of at least 400×400 px works best.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" required autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <p class="mt-2 text-sm text-on-surface">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="rounded-md text-sm text-on-surface-variant underline hover:text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-secondary">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4 border-t border-outline-variant pt-5">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-on-surface-variant"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</x-settings-card>
