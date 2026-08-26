# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Prestataires de services de proximité au Cameroun** — plombiers, électriciens,
coiffeuses, maçons, couturières, traiteurs, répétiteurs, mécaniciens. Ils travaillent
seuls ou en petite équipe, à Douala, Yaoundé, Bafoussam, Bamenda, Garoua. Ils consultent
la plateforme sur un téléphone Android de milieu de gamme, souvent en 3G, entre deux
interventions. C'est la seule population qui paie.

**Clients particuliers** — ils ont un besoin immédiat et concret (« une fuite sous
l'évier », « des tresses pour samedi », « du soutien en maths avant le bac ») et
cherchent quelqu'un de joignable et de fiable près de chez eux. Ils ne paient jamais
SmartLink.

**Administrateurs** — l'équipe SmartLink : modération des contenus signalés,
vérification des prestataires, gestion des catégories et des paliers d'abonnement.

## Product Purpose

Mettre en relation un client et un prestataire de proximité, puis s'effacer. Le succès
d'une mise en relation se mesure hors de la plateforme : la prestation a lieu, les deux
parties s'arrangent directement. SmartLink réussit quand un prestataire renouvelle son
abonnement parce qu'il en tire des clients.

## Positioning

**Un seul flux d'argent existe : du prestataire vers SmartLink**, sous forme d'un
abonnement mensuel en Mobile Money (HR-Skills Pay), après trente jours d'essai gratuit.

**Aucune somme ne transite entre client et prestataire.** Ni panier, ni acompte, ni
facture de prestation, ni commission. Le règlement se convient directement entre les deux
parties, hors plateforme ; les prix affichés sont indicatifs. C'est ce qui distingue
SmartLink d'une place de marché transactionnelle, et ce que l'interface doit énoncer
sans ambiguïté — un client qui croit payer en ligne est un client perdu.

Second point : la **recherche en langage naturel**. Le visiteur écrit sa phrase telle
qu'il la dirait, et la catégorie, la ville et le quartier en sont déduits.

## Operating Context

- **Mobile d'abord, sans nuance.** L'essentiel du trafic vient de téléphones Android en
  connexion variable. La largeur de référence est 390 px.
- **Français**, avec une bascule EN. Les termes techniques français sont plus longs que
  leurs équivalents anglais : les boutons et les libellés doivent en tenir compte.
- **Mobile Money** (MTN, Orange) est le seul moyen de paiement. Le prestataire valide sur
  son téléphone ; il n'existe aucun mandat récurrent, donc aucun prélèvement automatique.
- **Hébergement serverless** (Vercel + Supabase) : aucun worker, aucune tâche planifiée
  résidente, système de fichiers en lecture seule.

## Capabilities and Constraints

- Rôles : `client`, `provider`, `admin`. Un compte administrateur ne s'ouvre que par
  `php artisan admin:create` ; le formulaire public refuse ce rôle.
- Paliers d'abonnement : **Gratuit** (0 FCFA, 1 service, 3 demandes lisibles/mois),
  **Essentiel** (2 500 FCFA, 3 services, 20 demandes), **Pro** (7 500 FCFA, illimité,
  mise en avant, rédaction assistée, statistiques). Prix et plafonds se modifient depuis
  l'administration, sans redéploiement.
- Un prestataire sans abonnement valide sort des recherches ; ses demandes en cours et
  ses conversations restent accessibles. L'expiration masque, elle ne confisque pas.
- Cycle de vie d'une demande : `sent → viewed → accepted|refused → in_progress →
  completed`, plus `cancelled`. `RequestService` est le seul point d'entrée de ces
  transitions.
- Un avis n'est possible qu'après une demande terminée, une seule fois par demande.
- Assistant IA : conversation, recherche en langage naturel, aide à la rédaction,
  modération automatique. Repli permanent sur un mode par règles, sans coût et sans
  réseau.
- Montants toujours écrits avec l'espace insécable des milliers : « 5 000 FCFA ».

### Terminologie

**« Prestataire »**, partout et sans exception — interface, code, documentation. Ni
« artisan » (qui décrit mal un répétiteur, une coiffeuse ou un traiteur, tous déjà sur la
plateforme), ni « professionnel » (trop long pour un onglet de 78 px). Deux mots pour une
même personne se ressentent comme une hésitation.

**« Client »** pour celui qui cherche. **« Demande »** pour la mise en relation, jamais
« commande » ni « réservation » : rien ne se commande, rien ne se réserve.

### Pages légales

**Décision ouverte, travail engagé.** L'application n'a aujourd'hui ni conditions
générales, ni mentions légales, ni politique de confidentialité — alors qu'elle encaisse
en Mobile Money et héberge des données personnelles. Une première version est rédigée à
partir du fonctionnement réel de la plateforme et **marquée comme non validée
juridiquement** : elle doit être relue par un juriste avant l'ouverture au public.

## Brand Commitments

- Le nom **SmartLink** et le logo sont intangibles.
- **Voix : directe et concrète.** Vouvoiement, phrases courtes, les mots du métier. On
  énonce ce qui se passe et ce que ça coûte, sans enrobage : « Votre abonnement couvre
  30 jours à compter du paiement. » Un message d'erreur nomme le problème **et** la
  sortie. Ni tutoiement, ni registre soutenu : le premier sonne faux quand il est
  question d'argent, le second met à distance.
- **Décision ouverte :** les fichiers image du logo seront fournis plus tard. Le logo
  actuel est un composant Blade vectoriel (`x-application-logo`, deux nœuds reliés, en
  `currentColor`). Toute mise en page doit rester compatible avec le remplacement futur
  par une image fournie.
- Le vert `#005538` est l'ancre visuelle héritée, conservée par décision de refonte
  (« élévation » plutôt que remplacement) — pas un engagement de marque déclaré.

## Evidence on Hand

- `design/stitch-imports/` : 30 écrans Google Stitch avec leur HTML, plus
  `smartlink/DESIGN.md` — jetons de couleur, typographie, formes, principes. C'est
  l'autorité visuelle héritée.
- `CLAUDE.md` : contraintes d'ingénierie et pièges déjà rencontrés en production.
- `USAGE.md` : les parcours réels, rôle par rôle.
- Contenu de démonstration réel en base (`DemoSeeder`) : 14 prestataires, 27 services,
  13 demandes, 5 avis — de quoi juger les mises en page sur du contenu vrai plutôt que
  sur des pages vides.
