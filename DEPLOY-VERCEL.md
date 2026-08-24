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
derrière lui — à garder en tête pour une mise en production sérieuse.

---

## 2. Les services à ouvrir avant de déployer

### 2.1 Une base de données MySQL joignable depuis Internet

Au choix : PlanetScale, Railway, Aiven, Scaleway, ou un MySQL managé chez
n'importe quel hébergeur. Notez l'hôte, le port, le nom de la base,
l'utilisateur et le mot de passe.

Après le premier déploiement, il faut y appliquer les migrations. Depuis votre
machine, avec le `.env` pointé sur cette base :

```bash
php artisan migrate --force
php artisan db:seed --force   # catégories, formules d'abonnement, compte admin
```

### 2.2 Un stockage compatible S3

Les photos de profil, logos, images de services et pièces d'identité y sont
déposés. Cloudflare R2, Backblaze B2, Scaleway Object Storage ou Amazon S3 font
tous l'affaire.

Le bucket doit être **lisible publiquement** (les images sont affichées dans des
balises `<img>`), et vous devez connaître son domaine public — c'est la valeur
de `AWS_URL`.

Pour R2 ou B2, renseignez aussi `AWS_ENDPOINT`. Si le fournisseur n'accepte pas
les ACL par objet, mettez `AWS_VISIBILITY=private` et rendez le bucket public au
niveau du bucket : l'ACL par objet ferait échouer chaque dépôt.

### 2.3 Un compte HR-Skills Pay et une clé Anthropic (facultatif au départ)

L'application démarre sans : `PAYMENT_PROVIDER=mock` et `AI_DRIVER=rule` ne
demandent ni compte ni réseau. C'est le bon réglage pour une première mise en
ligne de démonstration.

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

DB_CONNECTION=mysql
DB_HOST=…
DB_PORT=3306
DB_DATABASE=smartlink
DB_USERNAME=…
DB_PASSWORD=…

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
AWS_DEFAULT_REGION=auto
AWS_BUCKET=smartlink
AWS_URL=https://cdn.example.com
AWS_ENDPOINT=                 # R2, B2, Scaleway…
AWS_USE_PATH_STYLE_ENDPOINT=false

# Protège la route appelée par Vercel Cron. Vercel envoie automatiquement
# « Authorization: Bearer <CRON_SECRET> » dès que cette variable existe.
CRON_SECRET=…                 # openssl rand -hex 32

AI_DRIVER=rule
PAYMENT_PROVIDER=mock
```

Ne cochez pas *Automatically expose System Environment Variables* pour y
chercher `APP_KEY` : générez-la vous-même et collez-la. Sans `APP_KEY`, aucune
session ne fonctionne.

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
- `routes` : `/index.php`, `/hot` et `/storage/…` sont renvoyés vers
  l'application plutôt que servis tels quels ; le reste passe par le CDN s'il
  existe, par PHP sinon.
- `crons` : `/cron/subscriptions-refresh` tous les jours à 01:00 **UTC**, soit
  02:00 à Douala — la même heure que le `schedule:run` de `routes/console.php`.

---

## 5. Vérifier après le premier déploiement

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
- **`php artisan` n'est pas disponible en ligne.** Migrations, seeds et
  commandes ponctuelles se lancent depuis votre machine, `.env` pointé sur la
  base de production.
- **`composer.lock` et `package-lock.json` sont ignorés par git** dans ce dépôt.
  Vercel résout donc les dépendances à neuf à chaque déploiement : c'est plus
  lent, et une version publiée entre deux déploiements peut changer le résultat.
  Pour un déploiement reproductible, versionnez ces deux fichiers.
- **La modération IA s'exécute dans la requête.** En `sync`, publier un service
  attend la réponse du modèle. Avec `AI_DRIVER=rule`, c'est instantané ; avec
  `AI_DRIVER=claude`, l'utilisateur attend l'appel.
- **`maxDuration` est à 30 s.** Le plan Hobby plafonne à 60 s ; un appel IA long
  couplé à une base lointaine peut s'en approcher.
- **Le runtime PHP n'est pas officiel.** Une montée de version de Vercel peut
  demander de bouger la version de `vercel-php` dans `vercel.json`.

Si ces limites gênent, une plateforme qui exécute Laravel nativement — Railway,
Render, Fly.io, Laravel Cloud, ou un simple VPS avec `INSTALL.md` — évite
l'ensemble de ces contorsions : disque persistant, worker de file d'attente et
cron système y fonctionnent sans adaptation.
