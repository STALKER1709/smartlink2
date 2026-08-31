<x-guest-layout>
    {{-- L'accueil ne porte ni barre de navigation ni pied de page : il se
         traverse, il ne se parcourt pas. --}}
    <div class="flex min-h-screen flex-col">
        <main class="mx-auto flex w-full max-w-container flex-1 flex-col items-center justify-center px-margin-mobile pb-40 md:px-margin-tablet lg:px-margin-desktop">
            <div class="relative mb-8 aspect-square w-full max-w-[320px] overflow-hidden rounded-xl border border-outline-variant bg-surface-container-high shadow-elevation-1 md:max-w-[480px]">
                <div class="flex h-full w-full items-center justify-center">
                    <x-icon :name="$contenu['icone']" size="3xl" filled class="text-primary/40" />
                </div>
            </div>

            <div class="max-w-[400px] text-center">
                <h1 class="mb-4 font-display-lg text-headline-lg sm:text-display-lg text-primary">{{ $contenu['titre'] }}</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">{{ $contenu['texte'] }}</p>
            </div>
        </main>

        <footer class="fixed bottom-0 z-10 flex w-full flex-col items-center justify-between gap-6 border-t border-outline-variant bg-surface-container px-margin-mobile py-6 md:flex-row">
            <div class="flex w-full justify-center gap-2 md:order-2 md:w-auto" role="img"
                 aria-label="{{ __('Étape :etape sur :total', ['etape' => $etape, 'total' => $total]) }}">
                @for ($i = 1; $i <= $total; $i++)
                    <span @class([
                        'h-2 rounded-full transition-all duration-300',
                        'w-8 bg-primary' => $i === $etape,
                        'w-2 bg-surface-variant' => $i !== $etape,
                    ])></span>
                @endfor
            </div>

            <div class="flex w-full justify-between md:order-1 md:w-auto md:gap-4">
                <form action="{{ route('onboarding.finish') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="rounded-full px-6 py-3 font-button-text text-button-text text-primary transition-colors duration-100 hover:bg-primary-container/10 active:scale-95">
                        {{ $etape === $total ? 'Fermer' : 'Passer' }}
                    </button>
                </form>

                @if ($etape < $total)
                    <x-primary-button :href="route('onboarding.show', $etape + 1)">
                        {{ __("Suivant") }}
                        <x-icon name="arrow_forward" />
                    </x-primary-button>
                @else
                    <form action="{{ route('onboarding.finish') }}" method="POST">
                        @csrf
                        <x-primary-button>
                            {{ __("Commencer") }}
                            <x-icon name="arrow_forward" />
                        </x-primary-button>
                    </form>
                @endif
            </div>
        </footer>
    </div>
</x-guest-layout>
