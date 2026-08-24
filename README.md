# SmartLink

SmartLink est une plateforme web qui met en relation des **clients** et des **prestataires de services** au Cameroun (plomberie, électricité, ménage, coiffure, cours particuliers, etc.). Le client trouve un prestataire, lui envoie une demande, échange avec lui par messagerie interne, suit l'avancement de la prestation, puis laisse un avis une fois le service terminé.

## Modèle économique

**Le prestataire paie, le client jamais.** SmartLink se finance par un abonnement mensuel souscrit par les prestataires, réglé en MTN Mobile Money ou Orange Money. Tout prestataire démarre par **30 jours d'essai gratuit** au niveau le plus complet, puis choisit son palier.

| Palier | Prix | Ce qu'il ouvre |
|---|---|---|
| Essai | gratuit, 30 jours | Les droits du palier Pro, sans engagement |
| Essentiel | 2 500 FCFA / mois | 3 services publiés, 20 demandes lisibles par mois, rédaction assistée |
| Pro | 7 500 FCFA / mois | Services et demandes illimités, mise en avant et badge, statistiques |

**Aucune somme ne transite entre client et prestataire dans l'application.** SmartLink ne prélève rien sur les prestations : il n'existe ni panier, ni acompte, ni facture de service, ni reversement. Le règlement de la prestation se convient et s'effectue directement entre les deux parties, hors plateforme. Le seul flux d'argent de l'application va du prestataire vers SmartLink.

À l'expiration d'un abonnement, les services du prestataire sont **retirés des recherches**, mais son compte, ses demandes en cours et ses conversations restent intacts : régler l'abonnement le fait réapparaître immédiatement.

## Ce que fait l'IA

Quatre fonctions, toutes bornées par les mêmes garde-fous de coût, et toutes accompagnées d'un repli qui laisse la plateforme utilisable si l'IA est coupée, injoignable ou hors budget.

| Fonction | Ce qu'elle apporte | Repli |
|---|---|---|
| **Assistant** | Répond sur le fonctionnement de la plateforme, en connaissant le catalogue réel | Réponses par mots-clés |
| **Recherche en langage naturel** | « J'ai une fuite sous l'évier à Bonamoussadi » remplit les filtres tout seul — accessible sans compte | Recherche par mot-clé |
| **Rédaction assistée** | Propose titre et description à un prestataire à partir de quelques mots | Le prestataire écrit lui-même |
| **Modération** | Pré-filtre annonces et avis, signale à l'administration | Contenu publié sans examen |

**L'assistant ne consulte aucune donnée personnelle et n'agit jamais à votre place** : ni votre compte, ni vos demandes, ni vos messages. Ce n'est pas qu'une consigne — rien dans le code ne lui donne accès à autre chose que le catalogue public.

**La modération signale, elle ne supprime jamais.** Un administrateur tranche.

Sans clé d'API, l'application fonctionne intégralement en mode par règles, sans aucun coût.

## Rôles

| Rôle | Ce qu'il peut faire |
|---|---|
| **Visiteur** | Parcourir les services et les profils prestataires, décrire son besoin, utiliser l'assistant |
| **Client** | Demander un service, échanger des messages, suivre ses demandes, laisser un avis |
| **Prestataire** | Publier/gérer ses services, répondre aux demandes, échanger avec ses clients, gérer son abonnement |
| **Administrateur** | Modérer les utilisateurs, les services et les catégories, traiter les contenus signalés |
| **Assistant IA** | Répondre aux questions sur la plateforme, les paliers et le fonctionnement des demandes |

## Stack technique

- **Backend** : Laravel 13 (PHP 8.3+), architecture MVC
- **Base de données** : MySQL en production, SQLite en développement/tests
- **Frontend** : Blade, Tailwind CSS, Alpine.js, Vite — interface responsive (mobile-first)
- **Paiement** : Mobile Money via HR-Skills Pay (MTN MoMo, Orange Money) — abonnements uniquement, avec un mode simulé sans compte
- **SMS** : Africa's Talking (vérification de numéro, relances d'échéance)
- **IA** : Claude (Anthropic), avec repli permanent sur un mode par règles sans coût
- **Tests** : PHPUnit (tests Feature + Unit)

## Documentation

- [INSTALL.md](INSTALL.md) — installation et configuration du projet
- [USAGE.md](USAGE.md) — guide d'utilisation par rôle, comptes de démonstration
- [ARCHITECTURE.md](ARCHITECTURE.md) — structure du code, modèles, services, choix techniques
- [DEPLOY-VERCEL.md](DEPLOY-VERCEL.md) — mise en production : Vercel, Supabase, HR-Skills Pay

## Démarrage rapide

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer dev
```

Voir [INSTALL.md](INSTALL.md) pour le détail de chaque étape, et [USAGE.md](USAGE.md) pour les comptes de démonstration créés par le seeder.

> **En production**, deux processus doivent tourner en permanence : un consommateur de file d'attente (`queue:work`, pour la modération) et le planificateur (`schedule:work`, pour les relances d'échéance et l'expiration des abonnements). Sans eux, ces fonctions restent inertes sans le moindre message d'erreur — voir [INSTALL.md](INSTALL.md#6-mise-en-production--deux-processus-indispensables).

## Tests

```bash
php artisan test
```

## Licence

Projet construit sur le framework [Laravel](https://laravel.com), open-source sous licence MIT.
