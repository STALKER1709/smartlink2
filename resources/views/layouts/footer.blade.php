{{--
    Pied de page.

    Il n'en existait aucun : les pages légales auraient été écrites sans être
    atteignables. Il porte aussi, une dernière fois et sur chaque page, la phrase
    qui distingue SmartLink d'une place de marché — un client qui croit payer en
    ligne est un client perdu.
--}}
<footer class="mt-16 border-t border-outline-variant bg-surface-container-low">
    <div class="mx-auto max-w-container px-margin-mobile py-10 md:px-margin-desktop">
        <div class="flex flex-col gap-8 md:flex-row md:justify-between">
            <div class="max-w-sm">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-primary">
                    <x-application-logo class="h-7 w-7" />
                    <span class="font-headline-md text-lg font-bold">SmartLink</span>
                </a>
                <p class="mt-3 text-sm text-on-surface-variant">
                    {{ __("Un artisan de confiance, près de chez vous. SmartLink met en relation et s'efface :") }}
                    {{ __('le règlement se convient directement avec le prestataire, hors plateforme.') }}
                    <span class="font-semibold text-primary">{{ __('Aucune commission.') }}</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 md:gap-12">
                <div>
                    <p class="font-headline-md text-sm font-semibold text-on-surface">{{ __('Découvrir') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('services.index') }}" class="text-on-surface-variant hover:text-primary">{{ __('Services') }}</a></li>
                        <li><a href="{{ route('providers.index') }}" class="text-on-surface-variant hover:text-primary">{{ __('Prestataires') }}</a></li>
                        <li><a href="{{ route('help.index') }}" class="text-on-surface-variant hover:text-primary">{{ __('Aide') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="font-headline-md text-sm font-semibold text-on-surface">{{ __('Prestataires') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @guest
                            <li><a href="{{ route('register') }}" class="text-on-surface-variant hover:text-primary">{{ __('Créer un compte') }}</a></li>
                        @else
                            <li><a href="{{ route('dashboard') }}" class="text-on-surface-variant hover:text-primary">{{ __('Tableau de bord') }}</a></li>
                        @endguest
                        <li><a href="{{ route('help.index') }}" class="text-on-surface-variant hover:text-primary">{{ __('Comment ça marche') }}</a></li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <p class="font-headline-md text-sm font-semibold text-on-surface">{{ __('Informations') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('legal.terms') }}" class="text-on-surface-variant hover:text-primary">{{ __('Conditions générales') }}</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="text-on-surface-variant hover:text-primary">{{ __('Confidentialité') }}</a></li>
                        <li><a href="{{ route('legal.notice') }}" class="text-on-surface-variant hover:text-primary">{{ __('Mentions légales') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-outline-variant pt-6 text-xs text-on-surface-variant sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} SmartLink · {{ __('Cameroun') }}</p>
            <p>{{ __('Gratuit pour les clients · Abonnement mensuel pour les prestataires') }}</p>
        </div>
    </div>
</footer>
