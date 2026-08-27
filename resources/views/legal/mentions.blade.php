@php
    $e = config('legal.editeur');
    $manquant = fn (?string $v) => $v ?: '— à renseigner —';
@endphp

<x-legal-page
    title="Mentions légales"
    intro="Qui édite SmartLink, qui l'héberge, et comment nous joindre."
    :sections="[
        'editeur' => 'Éditeur du site',
        'hebergement' => 'Hébergement',
        'propriete' => 'Propriété intellectuelle',
        'contenus' => 'Contenus publiés par les utilisateurs',
        'liens' => 'Liens et services tiers',
        'signalement' => 'Signaler un contenu',
    ]"
>
    <section id="editeur">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">1. Éditeur du site</h2>

        <dl class="mt-4 divide-y divide-outline-variant rounded-xl border border-outline-variant bg-surface-container-lowest">
            @foreach ([
                'Raison sociale' => $e['raison_sociale'],
                'Forme juridique' => $e['forme_juridique'],
                'Capital social' => $e['capital'],
                'Registre du commerce (RCCM)' => $e['rccm'],
                'Numéro d\'identifiant unique (NIU)' => $e['niu'],
                'Siège social' => $e['siege'],
                'Directeur de la publication' => $e['directeur_publication'],
                'Courriel' => $e['email'],
                'Téléphone' => $e['telephone'],
            ] as $libelle => $valeur)
                <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-baseline sm:gap-4">
                    <dt class="w-full shrink-0 text-sm text-on-surface-variant sm:w-64">{{ $libelle }}</dt>
                    <dd @class(['text-on-surface', 'text-outline' => ! $valeur])>{{ $manquant($valeur) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section id="hebergement">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">2. Hébergement</h2>

        <p class="mt-4">
            L'application est hébergée par <strong>Vercel Inc.</strong> (340 S Lemon Ave #4133, Walnut,
            CA 91789, États-Unis). Les données de compte, les photos et les pièces déposées sont
            conservées par <strong>Supabase Inc.</strong> (970 Toa Payoh North, Singapour).
        </p>
        <p class="mt-3">
            Ces deux prestataires opèrent hors du Cameroun. Les conséquences de ce choix pour vos
            données personnelles sont détaillées dans la
            <a href="{{ route('legal.privacy') }}" class="font-semibold text-primary">politique de confidentialité</a>.
        </p>
    </section>

    <section id="propriete">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">3. Propriété intellectuelle</h2>

        <p class="mt-4">
            Le nom SmartLink, son logo, la structure de l'application, ses textes d'interface et ses
            illustrations sont la propriété de l'éditeur. Toute reproduction ou réutilisation, même
            partielle, sans autorisation écrite préalable est interdite.
        </p>
        <p class="mt-3">
            Les polices de caractères employées — Hanken Grotesk, Source Sans 3, JetBrains Mono — et
            les icônes Material Symbols sont diffusées sous licences libres par leurs auteurs
            respectifs.
        </p>
    </section>

    <section id="contenus">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">4. Contenus publiés par les utilisateurs</h2>

        <p class="mt-4">
            Les annonces de services, les descriptions de profil, les photos, les messages et les avis
            sont publiés par les utilisateurs sous leur seule responsabilité. SmartLink les héberge et
            les met en forme ; il n'en est pas l'auteur et n'en garantit ni l'exactitude, ni la
            légalité.
        </p>
        <p class="mt-3">
            En les publiant, l'utilisateur accorde à SmartLink le droit de les afficher, de les
            reproduire et de les adapter techniquement dans le seul but de faire fonctionner le
            service — vignettes, extraits dans les résultats de recherche, aperçus.
        </p>
    </section>

    <section id="liens">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">5. Liens et services tiers</h2>

        <p class="mt-4">
            L'application s'appuie sur des services tiers pour l'encaissement des abonnements, l'envoi
            des SMS et les fonctions d'assistance automatique. Ils sont nommés un par un, avec les
            données qu'ils reçoivent, dans la
            <a href="{{ route('legal.privacy') }}#sous-traitants" class="font-semibold text-primary">politique de confidentialité</a>.
        </p>
        <p class="mt-3">
            SmartLink n'exerce aucun contrôle sur les sites vers lesquels un utilisateur pourrait
            renvoyer depuis son profil ou une conversation, et décline toute responsabilité quant à
            leur contenu.
        </p>
    </section>

    <section id="signalement">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">6. Signaler un contenu</h2>

        <p class="mt-4">
            Un contenu qui vous paraît illicite, trompeur ou offensant peut être signalé depuis la
            page où il apparaît. Chaque signalement est examiné par l'équipe de modération.
        </p>
        <p class="mt-3">
            Un examen automatique passe sur les annonces et les avis au moment de leur publication. Il
            <strong>signale seulement</strong> : il ne supprime rien et ne bloque personne. Toute
            décision de retrait est prise par une personne.
        </p>
    </section>

    <section id="credits">
        <h2 class="font-headline-lg text-headline-lg text-on-surface">7. Crédits photographiques</h2>

        <p class="mt-4">
            Les images qui illustrent les métiers et les pages publiques sont listées ci-dessous avec
            leur auteur et leur licence. Les photographies déposées par les prestataires sur leurs
            propres annonces restent leur responsabilité.
        </p>

        @include('partials.credits-photos')
    </section>
</x-legal-page>
