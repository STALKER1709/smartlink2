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

{{-- Pas de bandeau d'en-tête ici : le héros porte déjà le titre de la page.
     Les deux ensemble donnaient « Centre d'aide » puis « Comment pouvons-nous
     vous aider ? », deux titres pour une page qui n'en a qu'un. --}}
<x-app-layout :titre="__('seo.help')" :description="__('seo.help_description')">
    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-tablet lg:px-margin-desktop py-8">
        {{-- Le héros centré de la maquette : un panneau, un titre, une phrase,
             une barre de recherche. Aligné à gauche sur 1 150 px, le tout
             laissait les deux tiers de la ligne vides. --}}
        <section class="flex flex-col items-center gap-4 rounded-xl border border-outline-variant bg-surface-container-low px-6 py-12 text-center md:py-16">
            <h1 class="font-headline-lg text-headline-lg text-primary md:font-display-lg md:text-display-lg">
                {{ __("Comment pouvons-nous vous aider ?") }}
            </h1>
            <p class="max-w-2xl font-body-lg text-body-lg text-on-surface-variant">
                {{ __("Trouvez des réponses rapides à vos questions, ou demandez à l'assistant SmartLink.") }}
            </p>

            {{-- La recherche filtre les questions sur place, sans recharger :
                 neuf réponses ne valent pas un aller-retour au serveur. --}}
            <div class="relative mt-2 w-full max-w-2xl" x-data="{ q: '' }" x-init="$watch('q', v => {
                const terme = v.trim().toLowerCase();
                document.querySelectorAll('[data-faq]').forEach(el => {
                    el.hidden = terme !== '' && ! el.dataset.faq.includes(terme);
                });
                document.querySelectorAll('[data-faq-section]').forEach(sec => {
                    sec.hidden = ! sec.querySelector('[data-faq]:not([hidden])');
                });
            })">
                <x-icon name="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-outline" />
                <label for="faq-search" class="sr-only">{{ __("Rechercher de l'aide") }}</label>
                <input id="faq-search" type="search" x-model="q"
                       placeholder="{{ __('Rechercher de l\'aide (ex : paiements, compte…)') }}"
                       class="w-full rounded-xl border border-outline-variant bg-surface py-4 pl-12 pr-4 font-body-md text-body-md text-on-surface shadow-elevation-1 transition-all placeholder:text-on-surface-variant/70 focus:border-primary focus:ring-2 focus:ring-primary-container/20">
            </div>
        </section>

        {{-- Un sommaire de quatre pavés menait aux quatre sections
             immédiatement en dessous, et répétait leurs titres. Neuf
             questions ne se naviguent pas : elles se lisent. --}}

        <div class="mx-auto mt-10 max-w-3xl space-y-8">
            @foreach ($faqs as $key => $section)
                <div id="faq-{{ $key }}" data-faq-section>
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-4">{{ $section['label'] }}</h2>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl divide-y divide-outline-variant overflow-hidden">
                        @foreach ($section['items'] as $item)
                            <details class="group p-5" data-faq="{{ Str::lower($item['q'].' '.$item['a']) }}">
                                <summary class="flex items-center justify-between gap-3 cursor-pointer font-medium text-on-surface list-none">
                                    {{ $item['q'] }}
                                    <x-icon name="expand_more" class="text-on-surface-variant transition-transform group-open:rotate-180" />
                                </summary>
                                <p class="mt-3 text-label-md text-on-surface-variant">{{ $item['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mx-auto mt-10 max-w-3xl rounded-xl border border-outline-variant bg-secondary-container/20 p-6">
            <h2 class="font-headline-md text-headline-md text-primary mb-2">{{ __("Besoin d'une assistance personnalisée ?") }}</h2>
            <p class="text-label-md text-on-surface-variant">
                {{ __("Utilisez l'assistant SmartLink (icône en bas à droite de l'écran) pour poser directement votre question.") }}
            </p>
        </div>
    </div>
</x-app-layout>
