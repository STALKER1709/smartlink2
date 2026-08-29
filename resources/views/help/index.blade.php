@php
    $faqs = [
        'compte' => [
            'label' => __('Compte & Profil'),
            'icon' => 'account_circle',
            'items' => [
                ['q' => __('Comment créer un compte ?'), 'a' => __("Cliquez sur « S'inscrire », choisissez si vous êtes client ou prestataire, puis renseignez vos informations. Un profil est créé automatiquement selon votre rôle.")],
                ['q' => __('Comment modifier mes informations ?'), 'a' => __('Rendez-vous dans « Paramètres du compte » (ou « Mon profil client / prestataire ») depuis le menu en haut à droite.')],
            ],
        ],
        'demandes' => [
            'label' => __('Services & Demandes'),
            'icon' => 'assignment',
            'items' => [
                ['q' => __('Comment trouver un prestataire ?'), 'a' => __('Parcourez les services ou les prestataires depuis le menu, filtrez par catégorie, ville ou mot-clé, puis consultez les profils vérifiés.')],
                ['q' => __('Comment envoyer une demande ?'), 'a' => __("Depuis la fiche d'un service ou d'un prestataire, cliquez sur « Faire une demande », décrivez votre besoin et envoyez. Vous pouvez suivre son statut dans « Mes demandes ».")],
                ['q' => __('Que se passe-t-il une fois la demande acceptée ?'), 'a' => __('Une conversation s\'ouvre automatiquement avec le prestataire pour échanger les détails, avant que la prestation ne démarre puis se termine.')],
            ],
        ],
        'paiement' => [
            'label' => __('Paiement'),
            'icon' => 'payments',
            'items' => [
                ['q' => __('SmartLink prend-il une commission ?'), 'a' => __("Non. SmartLink ne prélève absolument rien sur vos échanges avec un prestataire : ce n'est pas une place de marché, seulement un outil de mise en relation.")],
                ['q' => __("Comment se passe le règlement d'un service ?"), 'a' => __('Le règlement se négocie et s\'effectue directement entre le client et le prestataire, en dehors de la plateforme. Les prix affichés sont indicatifs.')],
            ],
        ],
        'securite' => [
            'label' => __('Confiance & Sécurité'),
            'icon' => 'shield',
            'items' => [
                ['q' => __('Que signifie « Prestataire vérifié » ?'), 'a' => __("Un badge de vérification indique qu'un administrateur a validé la pièce d'identité du prestataire.")],
                ['q' => __('Comment laisser un avis ?'), 'a' => __("Une fois une demande marquée « Terminée », vous pouvez noter la prestation et laisser un commentaire depuis la page de la demande.")],
            ],
        ],
    ];
@endphp

<x-app-layout :titre="__('seo.help')" :description="__('seo.help_description')">
    <x-slot name="header">
        <x-page-header :title="__('Centre d\'aide')" />
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="max-w-2xl">
            <h1 class="mb-4 font-headline-lg text-headline-lg text-primary md:font-headline-xl md:text-headline-xl">
                {{ __("Comment pouvons-nous vous aider ?") }}
            </h1>
            <p class="mb-8 font-body-lg text-body-lg text-on-surface-variant">
                {{ __("Trouvez des réponses rapides à vos questions, ou demandez à l'assistant SmartLink.") }}
            </p>

            {{-- La recherche filtre les questions sur place, sans recharger :
                 neuf réponses ne valent pas un aller-retour au serveur. --}}
            <div class="relative w-full max-w-xl" x-data="{ q: '' }" x-init="$watch('q', v => {
                const terme = v.trim().toLowerCase();
                document.querySelectorAll('[data-faq]').forEach(el => {
                    el.hidden = terme !== '' && ! el.dataset.faq.includes(terme);
                });
                document.querySelectorAll('[data-faq-section]').forEach(sec => {
                    sec.hidden = ! sec.querySelector('[data-faq]:not([hidden])');
                });
            })">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline" aria-hidden="true">search</span>
                <label for="faq-search" class="sr-only">{{ __("Rechercher de l'aide") }}</label>
                <input id="faq-search" type="search" x-model="q"
                       placeholder="{{ __('Rechercher de l\'aide (ex : paiements, compte…)') }}"
                       class="w-full rounded-xl border border-outline-variant bg-surface py-4 pl-12 pr-4 font-body-md text-body-md text-on-surface shadow-sm transition-all placeholder:text-on-surface-variant/70 focus:border-primary focus:ring-2 focus:ring-primary-container/20">
            </div>
        </div>

        {{-- Un sommaire de quatre pavés menait aux quatre sections
             immédiatement en dessous, et répétait leurs titres. Neuf
             questions ne se naviguent pas : elles se lisent. --}}

        <div class="mt-10 space-y-8">
            @foreach ($faqs as $key => $section)
                <div id="faq-{{ $key }}" data-faq-section>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-4">{{ $section['label'] }}</h2>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl divide-y divide-outline-variant overflow-hidden">
                        @foreach ($section['items'] as $item)
                            <details class="group p-5" data-faq="{{ Str::lower($item['q'].' '.$item['a']) }}">
                                <summary class="flex items-center justify-between gap-3 cursor-pointer font-medium text-on-surface list-none">
                                    {{ $item['q'] }}
                                    <span class="material-symbols-outlined text-on-surface-variant transition-transform group-open:rotate-180">expand_more</span>
                                </summary>
                                <p class="mt-3 text-label-lg text-on-surface-variant">{{ $item['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 bg-secondary-container/20 border border-outline-variant rounded-xl p-6">
            <h2 class="font-headline-md text-headline-md text-primary mb-2">{{ __("Besoin d'une assistance personnalisée ?") }}</h2>
            <p class="text-label-lg text-on-surface-variant">
                {{ __("Utilisez l'assistant SmartLink (icône en bas à droite de l'écran) pour poser directement votre question.") }}
            </p>
        </div>
    </div>
</x-app-layout>
