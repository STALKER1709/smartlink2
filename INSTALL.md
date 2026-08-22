# Installation

## Prérequis

- PHP 8.3 ou supérieur, avec les extensions habituelles de Laravel (`pdo_sqlite` et/ou `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`)
- Composer 2
- Node.js 18+ et npm
- MySQL 8+ (recommandé en production) — SQLite suffit pour le développement local et les tests

## 1. Récupérer le projet et les dépendances

```bash
git clone <url-du-depot> smartlink
cd smartlink
composer install
npm install
```

## 2. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Le fichier `.env.example` est déjà pré-rempli pour un usage francophone (`APP_LOCALE=fr`, données de test en `fr_FR`). Les variables à vérifier/adapter :

```env
APP_URL=http://localhost
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartlink
DB_USERNAME=root
DB_PASSWORD=

AI_DRIVER=rule
```

### Services externes

Trois intégrations ont chacune un mode simulé, qui permet de développer et de tester sans aucun compte. Renseigner les identifiants suffit à basculer sur le service réel — aucun code ne change.

| Service | Variables | Sans identifiants |
|---|---|---|
| **Campay** (Mobile Money) | `CAMPAY_USERNAME`, `CAMPAY_PASSWORD`, `CAMPAY_WEBHOOK_SECRET` | Toute collecte réussit immédiatement, en simulation |
| **Africa's Talking** (SMS) | `AT_API_KEY`, `AT_SENDER_ID` | Les SMS sont écrits dans les journaux |
| **Claude** (IA) | `ANTHROPIC_API_KEY`, `AI_DRIVER=claude` | L'assistant répond par mots-clés, sans coût |

> **`CAMPAY_WEBHOOK_SECRET` est obligatoire en production.** Le rappel de l'opérateur est le seul canal qui crédite un abonnement, et le payeur lit sa propre référence de paiement à l'écran. Sans secret configuré, ce point d'entrée refuse tout et répond 503 : c'est délibéré, il vaut mieux ne rien créditer que de créditer n'importe qui. Renseignez la même valeur côté Campay et dans `.env`.

### Base de données

**Avec MySQL** (recommandé en production) : créez une base vide nommée `smartlink` (ou le nom choisi dans `DB_DATABASE`), puis assurez-vous que les identifiants `DB_USERNAME`/`DB_PASSWORD` sont corrects.

```sql
CREATE DATABASE smartlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Avec SQLite** (rapide pour développer en local) :

```env
DB_CONNECTION=sqlite
```

```bash
touch database/database.sqlite
```

> Les tests automatisés (`php artisan test`) utilisent toujours SQLite en mémoire (configuré dans `phpunit.xml`), quelle que soit la configuration de `.env`.

## 3. Lancer les migrations et les données de démonstration

```bash
php artisan migrate
php artisan db:seed
```

Ou en une seule commande (réinitialise puis peuple la base) :

```bash
php artisan migrate:fresh --seed
```

Le seeder crée des catégories de services, des comptes de démonstration pour chaque rôle (voir [USAGE.md](USAGE.md)), des services publiés et des demandes à différents stades du cycle de vie.

## 4. Lier le stockage public

Les images de services, logos et photos de profil sont stockées sur le disque `public` et exposées via un lien symbolique :

```bash
php artisan storage:link
```

## 5. Compiler les assets et démarrer l'application

En développement, `composer dev` lance en parallèle le serveur PHP, le worker de file d'attente, les logs (`pail`) et Vite :

```bash
composer dev
```

Ou manuellement :

```bash
php artisan serve
npm run dev
```

L'application est alors accessible sur `http://localhost:8000` (ou l'URL indiquée par `php artisan serve`).

### Build de production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Mise en production : deux processus indispensables

En développement, `composer dev` lance déjà tout. **En production, deux processus doivent tourner en permanence**, sans quoi des fonctions entières restent silencieusement inertes.

### La file d'attente

La modération automatique des annonces et des avis y passe. Sans consommateur, les contenus sont publiés mais jamais examinés — sans le moindre message d'erreur.

```bash
php artisan queue:work --tries=3 --timeout=120
```

À superviser avec systemd, supervisor ou l'équivalent de votre hébergeur, avec redémarrage automatique.

### Le planificateur

Il fait tourner `subscriptions:refresh` chaque nuit à 2 h. Sans lui : aucune relance SMS avant échéance, aucun abonnement ne passe en « expiré », et les prestataires arrivés à leur plafond mensuel de demandes ne réapparaissent jamais dans les recherches au changement de mois.

```bash
php artisan schedule:work
```

Ou, en cron classique :

```cron
* * * * * cd /chemin/vers/smartlink && php artisan schedule:run >> /dev/null 2>&1
```

### Vérifier que tout est en place

```bash
php artisan schedule:list          # doit lister subscriptions:refresh
php artisan subscriptions:refresh  # exécution manuelle, pour contrôle
php artisan queue:monitor default  # profondeur de la file
```

## 7. Lancer les tests

```bash
php artisan test
```

## 8. Vérifier le style de code (optionnel)

Le projet utilise [Laravel Pint](https://laravel.com/docs/pint) :

```bash
vendor/bin/pint          # corrige le style
vendor/bin/pint --test   # vérifie sans modifier
```
