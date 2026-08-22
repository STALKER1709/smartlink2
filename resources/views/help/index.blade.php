@php
    $faqs = [
        'compte' => [
            'label' => 'Compte & Profil',
            'icon' => 'account_circle',
            'items' => [
                ['q' => 'Comment créer un compte ?', 'a' => "Cliquez sur « S'inscrire », choisissez si vous êtes client ou prestataire, puis renseignez vos informations. Un profil est créé automatiquement selon votre rôle."],
                ['q' => 'Comment modifier mes informations ?', 'a' => 'Rendez-vous dans « Paramètres du compte » (ou « Mon profil client / prestataire ») depuis le menu en haut à droite.'],
            ],
        ],
        'demandes' => [
            'label' => 'Services & Demandes',
            'icon' => 'assignment',
            'items' => [
                ['q' => 'Comment trouver un prestataire ?', 'a' => 'Parcourez les services ou les prestataires depuis le menu, filtrez par catégorie, ville ou mot-clé, puis consultez les profils vérifiés.'],
                ['q' => 'Comment envoyer une demande ?', 'a' => "Depuis la fiche d'un service ou d'un prestataire, cliquez sur « Faire une demande », décrivez votre besoin et envoyez. Vous pouvez suivre son statut dans « Mes demandes »."],
                ['q' => 'Que se passe-t-il une fois la demande acceptée ?', 'a' => 'Une conversation s\'ouvre automatiquement avec le prestataire pour échanger les détails, avant que la prestation ne démarre puis se termine.'],
            ],
        ],
        'paiement' => [
            'label' => 'Paiement',
            'icon' => 'payments',
            'items' => [
                ['q' => 'SmartLink prend-il une commission ?', 'a' => "Non. SmartLink ne prélève absolument rien sur vos échanges avec un prestataire : ce n'est pas une place de marché, seulement un outil de mise en relation."],
                ['q' => "Comment se passe le règlement d'un service ?", 'a' => 'Le règlement se négocie et s\'effectue directement entre le client et le prestataire, en dehors de la plateforme. Les prix affichés sont indicatifs.'],
            ],
        ],
        'securite' => [
            'label' => 'Confiance & Sécurité',
            'icon' => 'shield',
            'items' => [
                ['q' => 'Que signifie « Prestataire vérifié » ?', 'a' => "Un badge de vérification indique qu'un administrateur a validé la pièce d'identité du prestataire."],
                ['q' => 'Comment laisser un avis ?', 'a' => "Une fois une demande marquée « Terminée », vous pouvez noter la prestation et laisser un commentaire depuis la page de la demande."],
            ],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-headline-md text-headline-md text-on-surface">Centre d'aide</h2>
    </x-slot>

    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop py-8">
        <div class="max-w-2xl">
            <h1 class="font-headline-xl text-headline-xl text-primary mb-3">Comment pouvons-nous vous aider ?</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">
                Trouvez des réponses rapides à vos questions, ou utilisez l'assistant en bas de l'écran pour une aide immédiate.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($faqs as $key => $section)
                <a href="#faq-{{ $key }}" class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 hover:bg-surface-container-low hover:border-primary/40 transition-colors flex flex-col gap-3">
                    <span class="material-symbols-outlined text-primary text-2xl">{{ $section['icon'] }}</span>
                    <h3 class="font-headline-md text-base font-semibold text-on-surface">{{ $section['label'] }}</h3>
                    <p class="text-sm text-on-surface-variant">{{ count($section['items']) }} question{{ count($section['items']) > 1 ? 's' : '' }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-10 space-y-8">
            @foreach ($faqs as $key => $section)
                <div id="faq-{{ $key }}">
                    <h2 class="font-headline-lg text-headline-lg text-on-surface mb-4">{{ $section['label'] }}</h2>
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl divide-y divide-outline-variant overflow-hidden">
                        @foreach ($section['items'] as $item)
                            <details class="group p-5">
                                <summary class="flex items-center justify-between gap-3 cursor-pointer font-medium text-on-surface list-none">
                                    {{ $item['q'] }}
                                    <span class="material-symbols-outlined text-on-surface-variant transition-transform group-open:rotate-180">expand_more</span>
                                </summary>
                                <p class="mt-3 text-sm text-on-surface-variant">{{ $item['a'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 bg-secondary-container/20 border border-outline-variant rounded-xl p-6">
            <h2 class="font-headline-md text-headline-md text-primary mb-2">Besoin d'une assistance personnalisée ?</h2>
            <p class="text-sm text-on-surface-variant">
                Utilisez l'assistant SmartLink (icône en bas à droite de l'écran) pour poser directement votre question.
            </p>
        </div>
    </div>
</x-app-layout>
