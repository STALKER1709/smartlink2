# SmartLink

SmartLink est une plateforme web qui met en relation des **clients** et des **prestataires de services** au Cameroun (plomberie, électricité, ménage, coiffure, cours particuliers, etc.). Le client trouve un prestataire, lui envoie une demande, échange avec lui par messagerie interne, suit l'avancement de la prestation, puis laisse un avis une fois le service terminé.

**SmartLink ne gère aucun paiement.** Il n'existe ni passerelle de paiement, ni panier, ni facture, ni historique de transaction dans l'application : la mise en relation s'arrête à l'organisation du service, les échanges financiers se font hors plateforme, directement entre le client et le prestataire.

## Rôles

| Rôle | Ce qu'il peut faire |
|---|---|
| **Visiteur** | Parcourir les services et les profils prestataires, utiliser le chatbot |
| **Client** | Demander un service, échanger des messages, suivre ses demandes, laisser un avis |
| **Prestataire** | Publier/gérer ses services, répondre aux demandes, échanger avec ses clients |
| **Administrateur** | Modérer les utilisateurs, les services et les catégories |
| **Chatbot IA** | Répondre aux questions fréquentes (FAQ) des visiteurs et clients |

## Stack technique

- **Backend** : Laravel 13 (PHP 8.3+), architecture MVC
- **Base de données** : MySQL en production, SQLite en développement/tests
- **Frontend** : Blade, Tailwind CSS, Alpine.js, Vite — interface responsive (mobile-first)
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
