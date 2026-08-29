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
| **HR-Skills Pay** (Mobile Money) | `PAYMENT_PROVIDER=hrskills`, `HRSKILLS_CLE_A`, `HRSKILLS_CLE_B`, `HRSKILLS_WEBHOOK_SECRET` | Encaissement simulé : montant pair, succès ; impair, échec |
| **Africa's Talking** (SMS) | `AT_API_KEY`, `AT_SENDER_ID` | Les SMS sont écrits dans les journaux |
| **Claude** (IA) | `ANTHROPIC_API_KEY`, `AI_DRIVER=claude` | L'assistant répond par mots-clés, sans coût |
| **Relais e-mail** | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | `MAIL_MAILER=log` écrit les messages dans `storage/logs` |

> **`HRSKILLS_WEBHOOK_SECRET` est obligatoire en production.** Le rappel du fournisseur est le seul canal qui crédite un abonnement. Sans secret configuré, tout rappel est refusé : c'est délibéré, il vaut mieux ne rien créditer que de créditer n'importe qui. Renseignez la même valeur côté HR-Skills et dans `.env`.
>
> **Les deux clés doivent porter le même environnement** — soit les deux en `_test_`, soit les deux en `_live_`. Elles se copient séparément depuis le tableau de bord, et un mélange enverrait des appels de production authentifiés par un secret de test.

```bash
php artisan payment:check   # vérifie clés, environnement et secret de rappel
```

> **En production, `MAIL_MAILER` doit désigner un vrai relais.** L'e-mail ne sert
> qu'à la réinitialisation du mot de passe — mais c'est la seule voie de
> récupération d'un compte. Avec le défaut `log`, le formulaire affiche « lien
> envoyé », le message part dans un fichier, et l'utilisateur ne reçoit jamais
> rien : aucune erreur nulle part. `MAIL_FROM_ADDRESS` doit être sur un domaine
> que vous possédez et avez authentifié (SPF/DKIM), sans quoi les messages
> partent en indésirables. `php artisan deploy:check` contrôle les deux.

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

## 3 bis. Installation sur l'écran d'accueil

L'application est installable : `/manifest.json` décrit le nom, les couleurs et
les icônes, `public/sw.js` affiche une page d'attente quand le réseau manque.
Rien à configurer — mais deux points valent d'être connus.

Les icônes de `public/images/icone-*.png` sont **versionnées**, comme
`public/build`. Elles sont redessinées à partir de la marque par :

```bash
php artisan icons:app
```

Cette commande n'a de raison d'être lancée que si le logo change ; les fichiers
qu'elle produit se commitent avec le reste.

Le service worker ne met en cache **que** la page hors-ligne : jamais une page
du site. Une page HTML servie depuis un cache afficherait une demande déjà
acceptée ou un abonnement déjà réglé, sans que le visiteur puisse rien y faire.
Pour le retirer un jour du parc, il ne suffit pas d'effacer `public/sw.js` — il
reste installé chez ceux qui l'ont déjà ; il faut déployer un fichier qui
appelle `self.registration.unregister()`.

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
php artisan payment:check          # clés, environnement, secret de rappel
php artisan schedule:list          # doit lister subscriptions:refresh
php artisan subscriptions:refresh  # exécution manuelle, pour contrôle
php artisan queue:monitor default  # profondeur de la file
```

## 6 bis. Hébergement serverless (Vercel)

Sur un hébergement serverless, aucun de ces deux processus ne peut tourner et le disque ne survit pas d'une requête à l'autre. La mise en production de référence — Vercel pour l'application, Supabase pour la base et le stockage, HR-Skills Pay pour l'encaissement — est décrite pas à pas dans [DEPLOY-VERCEL.md](DEPLOY-VERCEL.md).

## 6 ter. Sauvegardes et supervision

Deux points qui ne se voient pas tant que tout va bien, et qui se paient très
cher le jour où on en a besoin. Sur un serveur classique, tout est à mettre en
place : rien n'est fourni.

**Sauvegarder la base et les fichiers déposés, pas seulement la base.** Les
images et les pièces d'identité vivent sur le disque (ou dans un seau S3) : une
restauration de la base seule rendrait les lignes qui pointent vers des images,
pas les images.

```cron
# Base, tous les jours à 3 h, avec 14 jours de rétention.
0 3 * * * pg_dump -Fc smartlink > /var/sauvegardes/smartlink-$(date +\%F).dump && find /var/sauvegardes -name 'smartlink-*.dump' -mtime +14 -delete

# Fichiers déposés, vers un stockage distant — une copie sur le même disque
# ne protège de rien.
30 3 * * * rsync -a --delete /chemin/vers/smartlink/storage/app/ sauvegarde:/smartlink/
```

Une sauvegarde jamais restaurée n'est pas une sauvegarde : vérifiez-en une
(`pg_restore` dans une base d'essai) avant d'en avoir besoin. La copie des
pièces d'identité mérite une précaution de plus — c'est une copie de pièces
d'identité : elle se chiffre et ne se dépose pas n'importe où.

**Être prévenu quand quelque chose casse.** Aucune erreur de production n'est
signalée par défaut : elle part dans `storage/logs`, et personne ne lit un
fichier de journal de sa propre initiative. Le plus simple ne demande aucune
dépendance — Laravel sait déjà pousser les erreurs critiques vers un webhook :

```dotenv
LOG_CHANNEL=stack
LOG_STACK=daily,slack
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/…
LOG_LEVEL=error
```

`php artisan deploy:check` signale l'absence de destination d'alerte en
production. Un service de suivi d'erreurs (Sentry, Bugsnag, Flare) apporte en
plus la pile d'appels et le regroupement des occurrences : c'est le pas
d'après, il demande un paquet Composer.

Surveillez aussi les deux processus qui n'ont pas de symptôme visible quand ils
s'arrêtent : `queue:work` et `schedule:run`. Une file arrêtée laisse la
modération en attente sans qu'aucune page ne change, et un planificateur arrêté
laisse les abonnements échus actifs indéfiniment. `php artisan queue:monitor`
et une alerte sur l'âge du dernier passage de `subscriptions:refresh` couvrent
les deux.

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
