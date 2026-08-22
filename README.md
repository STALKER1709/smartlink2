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

## Rôles

| Rôle | Ce qu'il peut faire |
|---|---|
| **Visiteur** | Parcourir les services et les profils prestataires, utiliser le chatbot |
| **Client** | Demander un service, échanger des messages, suivre ses demandes, laisser un avis |
| **Prestataire** | Publier/gérer ses services, répondre aux demandes, échanger avec ses clients, gérer son abonnement |
| **Administrateur** | Modérer les utilisateurs, les services et les catégories |
| **Assistant IA** | Répondre aux questions sur la plateforme, les paliers et le suivi des demandes |

## Stack technique

- **Backend** : Laravel 13 (PHP 8.3+), architecture MVC
- **Base de données** : MySQL en production, SQLite en développement/tests
- **Frontend** : Blade, Tailwind CSS, Alpine.js, Vite — interface responsive (mobile-first)
- **Paiement** : Mobile Money via Campay (MTN MoMo, Orange Money) — abonnements uniquement
- **SMS** : Africa's Talking (vérification de numéro, relances d'échéance)
- **IA** : Claude (Anthropic), avec repli permanent sur un mode par règles sans coût
- **Tests** : PHPUnit (tests Feature + Unit)

## Documentation

- [INSTALL.md](INSTALL.md) — installation et configuration du projet
- [USAGE.md](USAGE.md) — guide d'utilisation par rôle, comptes de démonstration
- [ARCHITECTURE.md](ARCHITECTURE.md) — structure du code, modèles, services, choix techniques

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

## Tests

```bash
php artisan test
```

## Licence

Projet construit sur le framework [Laravel](https://laravel.com), open-source sous licence MIT.
