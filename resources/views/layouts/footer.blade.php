{{--
    Pied de page.

    Il n'en existait aucun : les pages légales auraient été écrites sans être
    atteignables. Il porte aussi, une dernière fois et sur chaque page, la phrase
    qui distingue SmartLink d'une place de marché — un client qui croit payer en
    ligne est un client perdu.
--}}
<footer class="mt-16 border-t border-outline-variant bg-surface-container-low">
    <div class="mx-auto max-w-container px-margin-mobile py-10 md:px-margin-tablet lg:px-margin-desktop">
        <div class="flex flex-col gap-10 lg:flex-row lg:justify-between">
            <div class="max-w-sm">
                <a href="{{ route('home') }}" class="-ml-2 inline-flex min-h-11 items-center gap-2 rounded-lg px-2 text-primary transition-colors hover:bg-surface-container" aria-label="{{ __('SmartLink — accueil') }}">
                    <x-application-logo class="h-7 w-7" />
                    <span class="font-headline-md text-headline-md font-bold">SmartLink</span>
                </a>
                <p class="mt-3 text-label-md text-on-surface-variant">
                    {{ __("Un artisan de confiance, près de chez vous. SmartLink met en relation et s'efface :") }}
                    {{ __('le règlement se convient directement avec le prestataire, hors plateforme.') }}
                    <span class="font-semibold text-primary">{{ __('Aucune commission.') }}</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3 lg:gap-x-12">
                <div>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ __('Découvrir') }}</p>
                    <ul class="mt-1 text-label-md">
                        <li><a href="{{ route('services.index') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Services') }}</a></li>
                        <li><a href="{{ route('providers.index') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Prestataires') }}</a></li>
                        <li><a href="{{ route('help.index') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Aide') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ __('Prestataires') }}</p>
                    <ul class="mt-1 text-label-md">
                        @guest
                            <li><a href="{{ route('register') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Créer un compte') }}</a></li>
                        @else
                            <li><a href="{{ route('dashboard') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Tableau de bord') }}</a></li>
                        @endguest
                        <li><a href="{{ route('help.index') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Comment ça marche') }}</a></li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <p class="font-headline-sm text-headline-sm text-on-surface">{{ __('Informations') }}</p>
                    <ul class="mt-1 text-label-md">
                        <li><a href="{{ route('legal.terms') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Conditions générales') }}</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Confidentialité') }}</a></li>
                        <li><a href="{{ route('legal.notice') }}" class="-mx-2 flex min-h-11 items-center rounded px-2 text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary">{{ __('Mentions légales') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-outline-variant pt-6 text-label-sm text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} SmartLink · {{ __('Cameroun') }}</p>
            <p>{{ __('Gratuit pour les clients · Abonnement mensuel pour les prestataires') }}</p>
        </div>
    </div>
</footer>
