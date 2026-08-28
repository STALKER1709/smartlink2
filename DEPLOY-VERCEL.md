# Déployer SmartLink sur Vercel

Ce guide décrit le déploiement de SmartLink sur Vercel. Il suppose l'application
telle qu'elle est dans ce dépôt, avec les fichiers `vercel.json`, `api/index.php`
et `.vercelignore` qui l'accompagnent.

> Pour un hébergement classique (VPS, Forge, Railway, o2switch…), c'est
> [`INSTALL.md`](INSTALL.md) qui fait foi. Rien de ce qui suit n'y est
> nécessaire.

---

## 1. Ce que Vercel change, et pourquoi ça compte

Vercel n'exécute pas un serveur : il exécute une **fonction**, démarrée à la
demande, détruite ensuite. Quatre conséquences, dont trois cassent l'application
en silence si on les ignore.

| Sur un serveur classique | Sur Vercel | Ce qu'on fait |
|---|---|---|
| `storage/` est écrivable | Tout est en lecture seule sauf `/tmp` | `api/index.php` déplace `storage/` vers `/tmp` au démarrage |
| Les images déposées restent sur le disque | Le disque disparaît à chaque déploiement — **et les images avec** | `MEDIA_DISK=s3` : les fichiers partent dans un bucket |
| `php artisan queue:work` tourne en permanence | Aucun processus ne peut tourner | `QUEUE_CONNECTION=sync` : la modération s'exécute dans la requête |
| `php artisan schedule:run` toutes les minutes | Pas de cron système | Vercel Cron appelle `/cron/subscriptions-refresh` |

Il n'y a pas non plus de base de données incluse : il en faut une, hébergée
ailleurs, joignable depuis Internet.

Le runtime PHP de Vercel est **communautaire** (`vercel-php`), pas un runtime
officiel. C'est un choix qui fonctionne, mais qui n'a pas le support de Vercel
derrière lui — à garder en tête pour une mise en production sérieuse. Le dépôt
est figé sur `vercel-php@0.8.0`, qui embarque PHP 8.4 — la version sur laquelle
les tests tournent. `0.9.0` existe et embarque PHP 8.5 : n'y passez qu'après
avoir relancé la suite sur cette version.

---

## 2. Les services à ouvrir avant de déployer

### 2.1 Un projet Supabase (PostgreSQL)

Créez un projet sur [supabase.com](https://supabase.com) et choisissez une
région proche du Cameroun — `eu-west-3` (Paris) fait l'affaire. Notez le mot de
passe de la base : il n'est affiché qu'une fois.

Dans **Project Settings → Database → Connection string**, prenez la chaîne
**Transaction pooler**, pas la connexion directe. En serverless chaque
invocation ouvre sa propre connexion : sans mutualisation, la limite du projet
est atteinte en pleine charge. L'hôte du pooler contient `pooler` —
`deploy:check` le vérifie et vous prévient si vous vous êtes trompé.

Trois réglages en découlent, chacun réglant une panne qui ne se signale pas
toute seule :

| Variable | Valeur | Pourquoi |
|---|---|---|
| `DB_HOST` | `aws-0-eu-west-3.pooler.supabase.com` | Le pooler, pas `db.<ref>.supabase.co` |
| `DB_SSLMODE` | `require` | Le défaut de Laravel (`prefer`) accepte une connexion en clair |
| `DB_EMULATE_PREPARES` | `true` | Le pooler en mode transaction ne garde pas les requêtes préparées côté serveur |

Le port du pooler en mode transaction est **6543**, l'utilisateur
`postgres.<ref-projet>` et la base `postgres`.

Après le premier déploiement, il faut appliquer les migrations. Depuis votre
machine, avec le `.env` pointé sur cette base :

```bash
php artisan migrate --force
```

Puis les données indispensables — et **elles seules** :

```bash
php artisan db:seed --class=PlanSeeder --force            # formules d'abonnement
php artisan db:seed --class=ServiceCategorySeeder --force # catégories de services
```

⚠️ **Ne lancez pas `php artisan db:seed` sans `--class` en production.** Le
seeder complet crée les comptes de démonstration décrits dans `USAGE.md`, dont
`admin@smartlink.cm` avec le mot de passe `password` : un compte
administrateur ouvert à qui a lu le dépôt.

Le compte administrateur se crée à la main, avec un mot de passe que vous
choisissez :

```bash
php artisan tinker
```

Puis, **sur une seule ligne** — Psy Shell affiche les invites `>>>` et `...`,
elles ne se tapent pas, et un bloc multiligne collé depuis un document les
ferait lire comme des opérateurs PHP :

```php
App\Models\User::create(['name' => 'Votre nom', 'email' => 'vous@votredomaine.cm', 'phone' => '+2376XXXXXXXX', 'password' => 'un-mot-de-passe-solide', 'role' => App\Models\User::ROLE_ADMIN]);
```

Vérifiez, puis sortez avec `exit` :

```php
App\Models\User::where('role', 'admin')->count();
```

Le mot de passe est haché automatiquement par le modèle.

### 2.2 Le stockage Supabase

Le même projet Supabase porte les fichiers déposés. Il faut **deux seaux**, et
la distinction n'est pas cosmétique.

1. **Storage → New bucket** : nommez-le `smartlink` et cochez **Public bucket**.
   Il porte les photos de profil, logos et images de services. Ces images
   s'affichent dans des balises `<img>` : un bucket privé les rendrait toutes
   cassées.
2. **Storage → New bucket** : nommez-le `smartlink-id-documents` et laissez-le
   **privé** (ne cochez pas « Public bucket »).

   Il porte les pièces d'identité déposées pour la vérification. Un seau public
   les servirait par leur URL, sans authentification : un nom de fichier
   aléatoire n'est pas un contrôle d'accès, et une URL qui fuit une fois
   (capture d'écran, en-tête `Referer`, journal d'un intermédiaire) ouvre le
   document définitivement. Ces fichiers ne sortent que par une route de
   l'application qui vérifie qui demande.

   `php artisan deploy:check` refuse le déploiement si ce seau est public ou
   confondu avec le premier.
3. **Project Settings → Storage → S3 access keys → New access key** : notez la
   clé et le secret, ils ne sont montrés qu'une fois.

Les trois valeurs qui en découlent, `<ref>` étant la référence du projet :

```dotenv
AWS_ENDPOINT=https://<ref>.supabase.co/storage/v1/s3
AWS_URL=https://<ref>.supabase.co/storage/v1/object/public/smartlink
AWS_USE_PATH_STYLE_ENDPOINT=true
```

⚠️ **`AWS_VISIBILITY=private`** avec Supabase. Ça paraît contre-intuitif pour un
bucket public, mais ce réglage ne décrit pas la visibilité du bucket : il dit à
Laravel de poser une ACL sur chaque objet déposé. Supabase ne gère pas les ACL
par objet et refuserait le dépôt. La lecture publique vient du bucket, cochée à
l'étape 1.

Cloudflare R2 se configure exactement pareil ; Amazon S3 accepte, lui,
`AWS_VISIBILITY=public`.

### 2.3 Un compte HR-Skills Pay

C'est le seul flux d'argent du produit : l'abonnement mensuel que le prestataire
règle en Mobile Money. Trois valeurs à récupérer, et un contrôle à passer.

```dotenv
PAYMENT_PROVIDER=hrskills
HRSKILLS_CLE_A=hrsk_pk_live_…
HRSKILLS_CLE_B=hrsk_sk_live_…
HRSKILLS_WEBHOOK_SECRET=…
```

Les deux clés doivent porter le **même** environnement — les deux en `_live_`,
ou les deux en `_test_`. Un mélange enverrait des appels de production
authentifiés par un secret de test. Vérifiez avant de déployer :

```bash
php artisan payment:check
```

⚠️ **`HRSKILLS_WEBHOOK_SECRET` n'est pas optionnel.** Sans lui, la signature des
rappels ne peut pas être vérifiée : la route se ferme (503) et **aucun
abonnement n'est jamais crédité**. Si elle s'ouvrait à la place, n'importe qui
connaissant une référence — le payeur la voit à l'écran — pourrait s'offrir des
cycles d'abonnement gratuits.

L'URL à déclarer chez HR-Skills est indiquée au §6.

Pour une première mise en ligne de démonstration, `PAYMENT_PROVIDER=mock`
n'appelle aucune API et ne demande aucun compte.

### 2.4 Un relais d'e-mail transactionnel

SmartLink notifie par SMS et dans l'application ; l'e-mail ne sert qu'à une
chose, mais elle est vitale : **la réinitialisation du mot de passe**. C'est la
seule voie de récupération d'un compte. Sans relais, un prestataire qui oublie
son mot de passe perd son compte et l'abonnement qu'il a payé.

Le défaut de Laravel est `MAIL_MAILER=log`, qui écrit le message dans un fichier
au lieu de l'envoyer — et sur Vercel ce fichier n'existe même pas. Rien
n'échoue, aucune erreur n'apparaît, le formulaire affiche « lien envoyé », et
personne ne reçoit jamais rien. C'est la panne la plus discrète de cette liste :
elle ne se voit que le jour où quelqu'un a vraiment besoin du lien.

N'importe quel relais transactionnel convient (Resend, Brevo, Postmark,
Mailgun…). Deux points à ne pas rater :

- `MAIL_FROM_ADDRESS` doit être sur **un domaine que vous possédez** et que vous
  avez authentifié chez le relais (SPF/DKIM). Une adresse en `example.com`, ou
  un domaine non signé, part en indésirables sans qu'aucun journal ne le dise.
- Ces envois sont synchrones sur Vercel (file en `sync`) : le formulaire attend
  la réponse du relais. Préférez l'API HTTP du fournisseur au SMTP quand elle
  est proposée, ou un port `587` en TLS — un `465` bloqué ferait expirer la
  requête.

`php artisan deploy:check` refuse un pilote qui n'envoie rien et signale une
adresse d'expédition restée à l'exemple.

### 2.5 Une clé Anthropic (facultatif)

`AI_DRIVER=rule` ne demande ni compte ni réseau et ne coûte rien : l'assistant
répond par règles. `AI_DRIVER=claude` avec `ANTHROPIC_API_KEY` active les
réponses du modèle — en gardant à l'esprit que la file d'attente est en `sync`
sur Vercel, donc la modération d'une annonce attend la réponse du modèle.

---

## 3. Les variables d'environnement à créer sur Vercel

Dans *Project → Settings → Environment Variables*, pour l'environnement
**Production** (et *Preview* si vous voulez des déploiements de test) :

```dotenv
APP_NAME=SmartLink
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:…            # php artisan key:generate --show
APP_URL=https://votre-projet.vercel.app

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

# Les journaux vont sur la sortie d'erreur : Vercel les collecte.
# Un fichier de log serait perdu à chaque requête.
LOG_CHANNEL=stderr
LOG_LEVEL=error

# Supabase, via le pooler en mode transaction — pas la connexion directe.
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-3.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.<ref-projet>
DB_PASSWORD=…
DB_SSLMODE=require            # « prefer » accepterait une connexion en clair
DB_EMULATE_PREPARES=true      # exigé par le pooler en mode transaction

SESSION_DRIVER=database
CACHE_STORE=database

# Indispensable : aucun worker ne peut tourner. En « database », les travaux
# de modération s'empileraient sans que rien ne les consomme, sans erreur.
QUEUE_CONNECTION=sync

# Indispensable : sinon chaque image déposée est perdue au déploiement suivant.
MEDIA_DISK=s3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=…
AWS_SECRET_ACCESS_KEY=…
AWS_DEFAULT_REGION=eu-west-3
AWS_BUCKET=smartlink
AWS_ENDPOINT=https://<ref-projet>.supabase.co/storage/v1/s3

# Indispensable : les pièces d'identité vont dans le seau privé, jamais dans
# celui des images. Sans ces deux lignes, elles seraient servies par leur URL.
ID_DOCUMENTS_DISK=s3_id_documents
AWS_ID_DOCUMENTS_BUCKET=smartlink-id-documents
AWS_URL=https://<ref-projet>.supabase.co/storage/v1/object/public/smartlink
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_VISIBILITY=private        # Supabase ne gère pas les ACL par objet

# Protège la route appelée par Vercel Cron. Vercel envoie automatiquement
# « Authorization: Bearer <CRON_SECRET> » dès que cette variable existe.
CRON_SECRET=…                 # openssl rand -hex 32

# Indispensable : le défaut « log » écrit le message dans un fichier au lieu
# de l'envoyer. C'est la seule voie de récupération d'un compte — sans un vrai
# relais, le mot de passe oublié annonce « lien envoyé » sans rien envoyer.
MAIL_MAILER=smtp
MAIL_HOST=…                   # relais transactionnel (Resend, Brevo, Postmark…)
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=contact@votre-domaine.cm   # un domaine que vous possédez
MAIL_FROM_NAME=SmartLink

PAYMENT_PROVIDER=hrskills
HRSKILLS_CLE_A=hrsk_pk_live_…
HRSKILLS_CLE_B=hrsk_sk_live_…
HRSKILLS_WEBHOOK_SECRET=…

AI_DRIVER=rule
```

Ne cochez pas *Automatically expose System Environment Variables* pour y
chercher `APP_KEY` : générez-la vous-même et collez-la. Sans `APP_KEY`, aucune
session ne fonctionne.

**Les huit lignes dont l'oubli ne produit aucune erreur visible.** Ce sont
celles que `deploy:check` et `/cron/health` contrôlent nommément, parce que
l'application démarre parfaitement sans elles :

| Ligne | Ce qui casse en silence |
|---|---|
| `QUEUE_CONNECTION=sync` | La modération des annonces et des avis ne s'exécute jamais |
| `MEDIA_DISK=s3` | Chaque image déposée disparaît au déploiement suivant |
| `ID_DOCUMENTS_DISK=s3_id_documents` | Les pièces d'identité sont servies par leur URL, sans authentification |
| `AWS_VISIBILITY=private` | Chaque dépôt d'image échoue chez Supabase |
| `CRON_SECRET` | Aucun abonnement n'expire, aucune relance n'est envoyée |
| `DB_SSLMODE=require` | La connexion à la base peut passer en clair |
| `HRSKILLS_WEBHOOK_SECRET` | Aucun abonnement n'est jamais crédité |
| `MAIL_MAILER=smtp` | Le mot de passe oublié annonce « lien envoyé » sans rien envoyer |

**Après un déploiement sur une base qui a déjà reçu des dépôts**, lancez une
fois `php artisan id-documents:secure` (ou `--dry-run` d'abord) : le code
n'écrit plus jamais sur le disque public, mais les pièces déposées avant ce
changement y restent joignables tant qu'elles n'ont pas été déplacées.

---

## 4. Déployer

```bash
npm i -g vercel
vercel login
vercel link          # rattache le dossier au projet Vercel
vercel --prod
```

Ou, plus simplement, connectez le dépôt GitHub au projet Vercel : chaque push
sur `main` déclenche un déploiement.

Ce que fait `vercel.json` :

- `installCommand` / `buildCommand` : `npm install && npm run build` — c'est
  Vite qui produit `public/build`, absent du dépôt.
- `outputDirectory: public` : les fichiers statiques (CSS, JS, polices) sont
  servis par le CDN, sans réveiller PHP.
- `functions` : `api/index.php` devient la fonction qui porte toute
  l'application. Le runtime `vercel-php` y lance `composer install`.
- `routes` : la racine `/` et tout chemin en `.php` sont renvoyés vers
  l'application **avant** le handler statique, sinon Vercel sert
  `public/index.php` comme un fichier — le navigateur télécharge le code source
  au lieu d'afficher le site. `/hot` et `/storage/…` suivent le même chemin. Le
  reste passe par le CDN s'il existe, par PHP sinon.

  ⚠️ `outputDirectory: public` expose le contenu de `public/` au CDN, et
  `public/index.php` y est le fichier d'index. C'est la raison d'être de la
  première règle : sans elle, la page d'accueil est un téléchargement.
- `crons` : `/cron/subscriptions-refresh` tous les jours à 01:00 **UTC**, soit
  02:00 à Douala — la même heure que le `schedule:run` de `routes/console.php`.
- `regions: ["cdg1"]` : Paris, la région la plus proche du Cameroun. Si votre
  plan refuse le choix de région, retirez simplement cette ligne.

`composer.lock`, `package-lock.json` et `public/build` sont **versionnés** dans
ce dépôt, contrairement à l'habitude Laravel. Deux raisons, toutes deux propres
au serverless : la fonction PHP doit trouver `public/build/manifest.json` dans
son propre système de fichiers pour construire les URL des assets, et la
reconstruction sur Vercel doit produire exactement les mêmes fichiers hachés que
ceux servis par le CDN — ce que seules des dépendances figées garantissent.

`/vendor` est en revanche exclu du dépôt Vercel (`.vercelignore`) : le runtime
lance `composer install` lui-même pendant la construction de la fonction.

---

## 5. Vérifier après le premier déploiement

Une seule commande répond à la question « est-ce que tout est réellement en
place ». Elle interroge l'application depuis l'intérieur de la production et
vérifie nommément les trois fonctions qui cassent sans message d'erreur :

```bash
curl -s -H "Authorization: Bearer $CRON_SECRET" \
     https://votre-projet.vercel.app/cron/health | jq
```

Elle répond `200` avec `"status": "ok"` quand rien ne bloque, `500` avec
`"status": "failed"` sinon — chaque point listé avec ce qui manque et la
conséquence. La même chose en local, avant de déployer :

```bash
php artisan deploy:check
```

Ensuite, les vérifications de base :

```bash
curl -s https://votre-projet.vercel.app/up          # doit répondre 200
curl -s https://votre-projet.vercel.app/ | head     # la page d'accueil
```

Puis, à la main :

1. Créer un compte prestataire → l'essai de 30 jours doit démarrer.
2. Déposer un logo → l'image doit s'afficher **et** son URL pointer vers
   `AWS_URL`, pas vers le domaine Vercel. Si elle pointe vers Vercel,
   `MEDIA_DISK` n'est pas pris en compte.
3. Publier un service → la modération doit s'exécuter tout de suite
   (`QUEUE_CONNECTION=sync`).
4. Déclencher le passage quotidien à la main :

```bash
curl -H "Authorization: Bearer $CRON_SECRET" \
     https://votre-projet.vercel.app/cron/subscriptions-refresh
```

Il doit répondre `{"command":"subscriptions:refresh","status":"ok",…}`. Sans
en-tête, il doit répondre 403 ; sans `CRON_SECRET` configuré, 503.

---

### Un mot sur HTTPS

L'application fait confiance aux en-têtes `X-Forwarded-*` du répartiteur
(`bootstrap/app.php`). Sans cela, Vercel transmettant la requête en HTTP en
interne, Laravel générerait des URL en `http://` : feuilles de style bloquées
pour contenu mixte et boucles de redirection sur les formulaires. Rien à
configurer, mais c'est la raison d'être de cette ligne — ne la retirez pas.

## 6. Le rappel de paiement HR-Skills

L'URL à déclarer côté HR-Skills est :

```
https://votre-projet.vercel.app/payments/webhook
```

Elle n'est vérifiée que si `HRSKILLS_WEBHOOK_SECRET` est renseigné — sans ce
secret, la route refuse tout (503) plutôt que de laisser n'importe qui déclarer
un abonnement réglé.

---

## 7. Limites connues de cet hébergement

- **Démarrage à froid.** Une requête sur une instance neuve recompile les vues
  Blade dans `/tmp` : comptez une seconde de plus. Les suivantes sont rapides.
- **Une migration ne s'applique pas toute seule au déploiement.** Vercel
  déploie le code, pas le schéma. Après tout push contenant une migration,
  lancez `php artisan migrate --force` depuis votre machine, `.env` pointé sur
  la base de production. `/cron/health` signale les migrations en attente :
  c'est le contrôle à relancer après chaque déploiement, parce que le symptôme
  est trompeur — les pages qui ne touchent pas à la colonne manquante
  continuent de répondre, et seule celle qui l'écrit tombe en 500.
- **`php artisan` n'est pas disponible en ligne.** Migrations, seeds et
  commandes ponctuelles se lancent depuis votre machine, `.env` pointé sur la
  base de production. Seul le contrôle d'après-déploiement a son pendant HTTP
  (`/cron/health`), justement parce qu'il doit voir la production de
  l'intérieur.
- **Toute nouvelle tâche planifiée doit avoir son pendant HTTP.** Ajouter une
  ligne à `routes/console.php` ne suffit pas ici : il faut une route protégée
  par `CRON_SECRET` et une entrée dans `crons` de `vercel.json`.
- **La modération IA s'exécute dans la requête.** En `sync`, publier un service
  attend la réponse du modèle. Avec `AI_DRIVER=rule`, c'est instantané ; avec
  `AI_DRIVER=claude`, l'utilisateur attend l'appel.
- **`memory` n'est plus réglable.** Vercel l'ignore sur sa facturation à l'usage
  processeur ; le réglage a été retiré de `vercel.json`. Seul `maxDuration`
  compte encore.
- **`maxDuration` est à 30 s.** Le plan Hobby plafonne à 60 s ; un appel IA long
  couplé à une base lointaine peut s'en approcher.
- **Rien n'est écrit dans le projet à l'exécution.** `storage/` et
  `bootstrap/cache/` sont déplacés vers `/tmp` par `api/index.php`. Si vous
  ajoutez du code qui écrit ailleurs dans l'arborescence, il échouera en
  production sans forcément le dire : le manifeste des fournisseurs de
  services, lui, se manifestait par un « Target class [view] does not exist »
  qui ne pointait vers rien.
- **Le runtime PHP n'est pas officiel.** Une montée de version de Vercel peut
  demander de bouger la version de `vercel-php` dans `vercel.json`.

Si ces limites gênent, une plateforme qui exécute Laravel nativement — Railway,
Render, Fly.io, Laravel Cloud, ou un simple VPS avec `INSTALL.md` — évite
l'ensemble de ces contorsions : disque persistant, worker de file d'attente et
cron système y fonctionnent sans adaptation.

---

## Annexe — sous Windows (PowerShell)

Les commandes de ce guide sont écrites pour un shell POSIX. Sous PowerShell,
quatre constructions n'existent pas : `grep`, le préfixe `VAR=valeur commande`,
`export`, et `curl` (qui y est un alias vers `Invoke-WebRequest`, de syntaxe
différente). Voici les équivalents.

### L'extension PostgreSQL

Elle est livrée avec PHP sous Windows, mais commentée dans `php.ini`.

```powershell
php -m | Select-String pdo_pgsql     # si rien ne sort, elle est inactive
php --ini                            # donne le chemin du php.ini chargé
```

Décommentez-y `extension=pdo_pgsql` et `extension=pgsql`, enregistrez, puis
rouvrez PowerShell.

### Les secrets

```powershell
php artisan key:generate --show
-join ((1..32) | ForEach-Object { '{0:x2}' -f (Get-Random -Maximum 256) })
```

La seconde ligne remplace `openssl rand -hex 32` pour `CRON_SECRET`.

### Les variables d'environnement d'une commande artisan

PowerShell n'accepte pas de préfixer une commande. On pose les variables, elles
valent pour toute la fenêtre :

```powershell
$env:DB_CONNECTION       = "pgsql"
$env:DB_HOST             = "aws-0-eu-west-3.pooler.supabase.com"
$env:DB_PORT             = "6543"
$env:DB_DATABASE         = "postgres"
$env:DB_USERNAME         = "postgres.VOTRE_REF"
$env:DB_PASSWORD         = "VOTRE_MDP"
$env:DB_SSLMODE          = "require"
$env:DB_EMULATE_PREPARES = "true"
```

⚠️ **Ces variables survivent à la commande.** Un `php artisan test` lancé
ensuite dans la même fenêtre irait taper dans la base de production. Fermez la
fenêtre une fois les migrations passées, ou nettoyez :

```powershell
Remove-Item Env:\DB_CONNECTION, Env:\DB_HOST, Env:\DB_PORT, Env:\DB_DATABASE, `
            Env:\DB_USERNAME, Env:\DB_PASSWORD, Env:\DB_SSLMODE, Env:\DB_EMULATE_PREPARES
```

### Les appels HTTP de vérification

Appelez le vrai binaire, `curl.exe`, livré avec Windows 10 et 11 :

```powershell
curl.exe -s -H "Authorization: Bearer VOTRE_CRON_SECRET" `
         https://votre-projet.vercel.app/cron/health | ConvertFrom-Json | ConvertTo-Json -Depth 5
```

Le caractère de continuation de ligne est l'accent grave (`` ` ``), pas la
barre oblique inverse.
