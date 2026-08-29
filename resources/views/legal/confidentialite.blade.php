<x-legal-page
    title="Politique de confidentialité"
    intro="Ce que SmartLink collecte, pourquoi, à qui cela est transmis, et ce que vous pouvez exiger."
    :sections="[
        'collecte' => 'Ce que nous collectons',
        'usage' => 'À quoi cela sert',
        'sous-traitants' => 'À qui cela est transmis',
        'ia' => 'Ce qui passe par une intelligence artificielle',
        'hors-cameroun' => 'Données hébergées hors du Cameroun',
        'duree' => 'Combien de temps',
        'droits' => 'Vos droits',
        'cookies' => 'Cookies',
    ]"
>
    <section id="collecte">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">1. Ce que nous collectons</h2>

        <p class="mt-4">Seulement ce que le service exige pour fonctionner.</p>

        <h3 class="mt-6 font-headline-md text-headline-md text-on-surface">De tout le monde</h3>
        <ul class="mt-2 list-disc space-y-1.5 pl-5">
            <li>Nom, adresse e-mail, numéro de téléphone, langue choisie.</li>
            <li>Ville et quartier, quand vous les renseignez.</li>
            <li>Les messages que vous échangez sur la plateforme.</li>
            <li>Adresse IP et journaux techniques des pages consultées.</li>
        </ul>

        <h3 class="mt-6 font-headline-md text-headline-md text-on-surface">Des prestataires, en plus</h3>
        <ul class="mt-2 list-disc space-y-1.5 pl-5">
            <li>Nom de l'activité, description, adresse, horaires, zones d'intervention.</li>
            <li>Logo, photos des services publiés.</li>
            <li>
                <strong>Une copie de pièce d'identité</strong>, si vous demandez le badge « vérifié ».
                Elle sert à cette seule vérification, n'est jamais affichée publiquement, et vous
                pouvez en demander la suppression une fois la vérification faite.
            </li>
            <li>
                Numéro de téléphone Mobile Money, opérateur, montants et références des abonnements
                réglés. <strong>SmartLink ne voit ni ne conserve votre code Mobile Money</strong> :
                vous le composez sur votre téléphone, chez votre opérateur.
            </li>
        </ul>

        <h3 class="mt-6 font-headline-md text-headline-md text-on-surface">Des clients, en plus</h3>
        <ul class="mt-2 list-disc space-y-1.5 pl-5">
            <li>Photo de profil, si vous en déposez une.</li>
            <li>Les demandes envoyées et les avis laissés après une prestation terminée.</li>
        </ul>

        <p class="mt-4">
            Aucune donnée bancaire n'est collectée. Aucune somme ne transitant entre client et
            prestataire, la plateforme n'a jamais à connaître ce que vous vous versez.
        </p>
    </section>

    <section id="usage">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">2. À quoi cela sert</h2>

        <ul class="mt-4 list-disc space-y-1.5 pl-5">
            <li>Vous faire trouver, ou trouver quelqu'un : c'est l'objet du service.</li>
            <li>Transmettre une demande et ouvrir la conversation qui suit.</li>
            <li>Prévenir par SMS d'une nouvelle demande, d'un message, ou d'une échéance d'abonnement.</li>
            <li>Encaisser l'abonnement d'un prestataire et en tenir l'historique.</li>
            <li>Vérifier une identité quand le badge est demandé.</li>
            <li>Modérer les contenus signalés et conserver la trace des décisions prises.</li>
        </ul>

        <p class="mt-4">
            Vos données ne sont ni vendues, ni louées, ni cédées à des fins publicitaires. Aucun
            traceur publicitaire n'est installé.
        </p>
    </section>

    <section id="sous-traitants">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">3. À qui cela est transmis</h2>

        <p class="mt-4">
            Cinq prestataires techniques reçoivent une partie de vos données pour faire fonctionner le
            service. La liste correspond aux appels réellement effectués par l'application.
        </p>

        <div class="mt-4 overflow-x-auto rounded-xl border border-outline-variant">
            <table class="w-full min-w-[42rem] border-collapse text-left text-label-lg">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th scope="col" class="p-3 font-semibold text-on-surface">Prestataire</th>
                        <th scope="col" class="p-3 font-semibold text-on-surface">Rôle</th>
                        <th scope="col" class="p-3 font-semibold text-on-surface">Données reçues</th>
                        <th scope="col" class="p-3 font-semibold text-on-surface">Lieu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant bg-surface-container-lowest">
                    @foreach (config('legal.sous_traitants') as $st)
                        <tr>
                            <th scope="row" class="p-3 font-semibold text-on-surface">{{ $st['nom'] }}</th>
                            <td class="p-3 text-on-surface-variant">{{ $st['role'] }}</td>
                            <td class="p-3 text-on-surface-variant">{{ $st['donnees'] }}</td>
                            <td class="p-3 text-on-surface-variant">{{ $st['lieu'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-4">
            Vos données peuvent en outre être communiquées à une autorité judiciaire ou administrative
            camerounaise qui en fait la demande dans les formes prévues par la loi.
        </p>
    </section>

    <section id="ia">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">4. Ce qui passe par une intelligence artificielle</h2>

        <p class="mt-4">
            Quatre fonctions de SmartLink font appel à un modèle d'intelligence artificielle fourni par
            <strong>Anthropic</strong>, une société établie aux États-Unis. Voici exactement ce qui lui
            est envoyé :
        </p>

        <ul class="mt-4 list-disc space-y-1.5 pl-5">
            <li>Le texte des questions posées à l'assistant, et l'historique de la conversation en cours.</li>
            <li>Les phrases tapées dans la recherche en langage naturel.</li>
            <li>Ce qu'un prestataire écrit lorsqu'il demande de l'aide à la rédaction d'une annonce.</li>
            <li>Le titre et la description d'un service publié, et le texte d'un avis déposé, pour l'examen automatique de modération.</li>
        </ul>

        <p class="mt-4">
            Ne lui sont <strong>jamais</strong> transmis : vos messages privés avec un prestataire ou un
            client, votre numéro de téléphone, votre pièce d'identité, ni aucune donnée de paiement.
        </p>

        <p class="mt-3">
            L'examen automatique de modération <strong>signale seulement</strong>. Il ne supprime rien,
            ne suspend personne et ne décide de rien : toute décision est prise par une personne de
            l'équipe. Aucune décision produisant un effet juridique n'est prise sur le seul fondement
            d'un traitement automatisé.
        </p>

        <p class="mt-3">
            Ces fonctions peuvent être désactivées par l'éditeur, auquel cas l'application bascule sur
            un mode par règles qui n'envoie rien à l'extérieur.
        </p>
    </section>

    <section id="hors-cameroun">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">5. Données hébergées hors du Cameroun</h2>

        <p class="mt-4">
            L'application et sa base de données sont hébergées hors du territoire camerounais, et
            trois des cinq prestataires listés plus haut le sont également. Vos données franchissent
            donc des frontières.
        </p>
        <p class="mt-3">
            En utilisant SmartLink, vous en êtes informé et vous y consentez. Si ce point vous
            préoccupe, écrivez-nous : c'est une question légitime, à laquelle nous répondrons
            précisément.
        </p>
    </section>

    <section id="duree">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">6. Combien de temps</h2>

        <ul class="mt-4 list-disc space-y-1.5 pl-5">
            <li><strong>Compte actif</strong> : tant que vous l'utilisez.</li>
            <li><strong>Après suppression</strong> : le compte est retiré des recherches immédiatement et effacé sous trente jours, sauf ce que la loi impose de conserver.</li>
            <li><strong>Pièce d'identité</strong> : effacée sur demande une fois la vérification faite.</li>
            <li><strong>Historique des abonnements</strong> : conservé le temps prévu par les obligations comptables.</li>
            <li><strong>Journaux techniques et d'audit</strong> : douze mois.</li>
        </ul>
    </section>

    <section id="droits">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">7. Vos droits</h2>

        <p class="mt-4">Vous pouvez à tout moment :</p>

        <ul class="mt-3 list-disc space-y-1.5 pl-5">
            <li>consulter et corriger vos informations depuis votre profil ;</li>
            <li>demander une copie de toutes les données vous concernant ;</li>
            <li>demander la suppression de votre compte ;</li>
            <li>vous opposer à l'envoi de SMS de notification ;</li>
            <li>demander que votre pièce d'identité soit effacée.</li>
        </ul>

        <p class="mt-4">
            Une demande adressée par courriel reçoit une réponse sous trente jours. Nous pouvons vous
            demander de confirmer votre identité avant d'y donner suite — c'est ce qui empêche
            quelqu'un d'obtenir vos données en se faisant passer pour vous.
        </p>
    </section>

    <section id="cookies">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">8. Cookies</h2>

        <p class="mt-4">
            SmartLink ne dépose que les cookies nécessaires à son fonctionnement : celui qui maintient
            votre session ouverte, celui qui protège les formulaires contre la soumission frauduleuse,
            et celui qui retient votre choix de langue.
        </p>
        <p class="mt-3">
            Aucun cookie publicitaire, aucun traceur de mesure d'audience tiers. Il n'y a donc pas de
            bandeau à accepter : il n'y a rien à refuser.
        </p>
    </section>
</x-legal-page>
