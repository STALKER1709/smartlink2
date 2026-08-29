# SmartLink — contexte pour Claude Code

## Travail en parallèle sur ce dépôt

Plusieurs sessions Claude (comptes différents) peuvent travailler simultanément sur ce
projet, chacune sur sa propre branche `claude/...`. **Avant de démarrer une tâche**,
vérifie l'état réel du dépôt plutôt que de supposer que la branche par défaut est à jour :

```bash
git fetch origin
git branch -a
git log --oneline --all -20
```

S'il existe une branche `claude/...` plus avancée que celle sur laquelle tu es (plus de
commits récents à partir du même ancêtre), pars de cette base (`git reset --hard
origin/<branche>` si ta propre branche n'a pas de commit unique, sinon rebase) au lieu
de dupliquer ou de contredire un travail déjà en cours. En cas de doute sur la base à
utiliser ou sur une zone déjà prise en charge par une autre session, demande à
l'utilisateur plutôt que de supposer.

## Aperçu du produit

SmartLink met en relation des **clients** et des **prestataires de services** au
Cameroun (plomberie, ménage, coiffure, cours particuliers, etc.).

### Le modèle économique, à ne pas se tromper dessus

**Un seul flux d'argent existe : du prestataire vers SmartLink**, sous forme d'un
abonnement mensuel réglé en Mobile Money via **HR-Skills Pay**. Tout prestataire
commence par 30 jours d'essai gratuit.

**Aucune somme ne transite entre client et prestataire.** Il n'y a ni panier, ni
**acompte**, ni facture de prestation, ni commission — l'acompte de 25 % qui existait au
début du projet a été retiré, et la table `payments` appartient désormais à une
`Subscription`, plus à une demande de service. Le règlement de la prestation se convient
directement entre les deux parties, hors plateforme. Les prix affichés sur les services
sont indicatifs.

Cette contrainte se vérifie dans le code (`app/Services/SubscriptionService.php`,
`app/Models/Payment.php`), dans les réponses de l'assistant
(`app/Services/Ai/SmartLinkContext.php`) et dans les tests. `README.md`,
`ARCHITECTURE.md` et `USAGE.md` sont à jour sur ce point depuis la refonte.

Le projet comprend par ailleurs un assistant IA (assistant conversationnel, recherche en
langage naturel, aide à la rédaction, modération automatique) basé sur `anthropic-ai/sdk`,
avec un repli permanent sur un mode par règles sans coût.

## Stack

- Backend : Laravel 13 (PHP 8.3+)
- Base : PostgreSQL (Supabase) en production, SQLite en dev-tests, MySQL possible.
  La suite tourne sur SQLite **et** sur PostgreSQL — vérifie les deux avant de
  toucher à une requête.
- Frontend : Blade, Tailwind CSS, Alpine.js, Vite
- Tests : PHPUnit (`php artisan test`)

## Conventions établies (à respecter)

- **Contrôleurs minces** : validation (Form Request) + autorisation (Policy via
  `$this->authorize()`) + délégation à un service métier (`app/Services`) + réponse HTTP.
- **Form Requests** ne gèrent que la validation ; leur `authorize()` renvoie `true`.
  L'autorisation fine passe uniquement par les Policies Eloquent (`app/Policies`).
- **Middleware `role:<role>`** protège les groupes de routes par rôle (première ligne de
  défense) ; les Policies gèrent la propriété de la ressource (deuxième ligne).
- **RequestService** est le seul point d'entrée pour faire transitionner une
  `ServiceRequest` (table de transitions strictes, historique, audit, notification).
- Chaque test Feature couvre le cas autorisé **et** le(s) cas refusé(s) (invité, mauvais
  rôle, mauvais propriétaire).
- Style de code : Laravel Pint (`vendor/bin/pint`).

## Avant de committer

```bash
composer install    # composer.lock est versionné : ne le régénère que pour un changement de dépendance
php artisan view:clear   # voir ci-dessous — indispensable après un changement de couleurs
npm install && npm run build
php artisan test
vendor/bin/pint --test
```

Et, dès que tu touches à une requête, à une contrainte ou à une migration, le
second passage sur le moteur de la production :

```bash
php artisan test --configuration=phpunit.pgsql.xml   # PostgreSQL local requis
```

⚠️ Le glob de contenu de Tailwind inclut `storage/framework/views`, le cache des vues
Blade compilées. Après un changement de classes, les anciennes survivent dans la feuille
produite tant que ce cache n'est pas vidé — on croit alors avoir une régression de style
alors que le code est juste. `php artisan view:clear` avant `npm run build`.

⚠️ Si tu travailles depuis un `git worktree` avec un `vendor/` lié par symlink,
l'autoloader optimisé pointe vers l'autre checkout et les classes nouvelles paraissent
manquantes (`Class ... does not exist`, routes « not defined »). Lance
`composer dump-autoload` dans le checkout que tu testes, ou teste sans worktree.

## Setup local rapide

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # ou configurer MySQL comme dans .env.example
php artisan migrate --seed
npm run build
```

## Hébergement

Le déploiement de référence reste un serveur classique (`INSTALL.md`) : disque persistant,
`queue:work` et `schedule:work` en continu.

Un déploiement serverless sur Vercel est aussi possible (`DEPLOY-VERCEL.md`,
`vercel.json`, `api/index.php`). Trois contraintes y sont structurelles et ne doivent pas
être reperdues :

- **Les fichiers déposés ne passent plus par `Storage::disk('public')` en dur.** Utilise
  `media_disk()` dans les contrôleurs et `media_url()` dans les vues
  (`app/Support/helpers.php`) : c'est ce qui permet de basculer sur S3 via `MEDIA_DISK`.
  Un `disk('public')` en dur réintroduit la perte silencieuse des images au déploiement.
- **Aucun worker ne tourne** : la file passe en `sync`. Ne compte pas sur un traitement
  différé pour quoi que ce soit d'indispensable.
- **Aucun `schedule:run`** : le passage quotidien est déclenché par
  `GET /cron/subscriptions-refresh`, protégé par `CRON_SECRET` (403 sans jeton, 503 sans
  secret configuré). Toute nouvelle tâche planifiée doit avoir son pendant HTTP.

⚠️ **Une pièce d'identité ne va jamais sur le disque `media`.** Ce disque est public :
ce qu'on y écrit est servi par le serveur web sans passer par Laravel, donc sans
middleware ni Policy — un nom de fichier aléatoire n'y change rien. Les pièces d'identité
passent par `id_documents_disk()` (privé) et ne ressortent que par
`ProviderVerificationController::document()`, qui vérifie la Policy `viewIdDocument`.
`deploy:check` refuse un disque public ou confondu avec `media`, et
`tests/Feature/Provider/IdDocumentPrivacyTest.php` monte la garde. Toute nouvelle donnée
sensible déposée par un utilisateur suit le même chemin.

⚠️ `composer.lock`, `package-lock.json` et `public/build` sont **versionnés**, contrairement
à l'habitude Laravel. La fonction PHP déployée doit trouver `public/build/manifest.json`, et
la reconstruction sur l'hébergeur doit produire exactement les fichiers hachés qui ont été
testés. Conséquence : `npm run build` fait partie de la préparation d'un commit dès qu'une
classe Tailwind ou un asset change, et le résultat se commite avec le reste.

⚠️ Dans `vercel.json`, la racine `/` doit être routée vers `api/index.php`
**avant** `{ "handle": "filesystem" }`. `outputDirectory` vaut `public`, et
`public/index.php` y est le fichier d'index : sans cette règle, le CDN sert le
code source du point d'entrée et la page d'accueil devient un téléchargement.

⚠️ **Rien ne doit être écrit dans l'arborescence du projet à l'exécution.** Sur
Vercel tout est en lecture seule sauf `/tmp` ; `api/index.php` y déplace
`storage/` **et** `bootstrap/cache/` — ce dernier parce que Laravel y écrit le
manifeste de ses fournisseurs de services à la première requête et que ce
fichier n'est pas versionné. Sans ce déplacement, aucun fournisseur ne
s'enregistre et l'application meurt sur « Target class [view] does not
exist », qui ne dit rien de la cause. `tests/Unit/ServerlessPathsTest.php`
monte la garde.

⚠️ `php artisan deploy:check` (ou `GET /cron/health` en ligne, avec le jeton) vérifie que
l'hébergement porte réellement les trois fonctions qui cassent en silence : stockage des
fichiers, file d'attente, passage quotidien. À lancer après chaque déploiement.

⚠️ **Dans les fichiers de configuration, utilise `env_or()` et non `env()`** pour
tout réglage dont une valeur nulle coûterait quelque chose. `env('X', 30)` ne
rend 30 que si `X` **n'existe pas** : une variable posée à vide sur l'hébergeur
— ce qui arrive dès qu'on colle un bloc `.env` sans remplir toutes les lignes —
donne `''`, et `(int) ''` vaut zéro. En production, `SUBSCRIPTION_CYCLE_DAYS`
vide a donné un abonnement payé couvrant zéro jour, et `HRSKILLS_BASE_URL` vide
une URL relative qui faisait échouer chaque encaissement.
`tests/Unit/EnvFallbackTest.php` monte la garde.

⚠️ **`User::activeSubscription()` est mémoïsé le temps de la requête.** Une seule page
de prestataire rejouait la requête jusqu'à sept fois — barre de navigation, bandeau
d'abonnement, bouton de publication et chaque appel de `QuotaService`. Invisible sur
SQLite, autant d'allers-retours réseau sur Supabase.

Conséquence : **toute écriture sur un abonnement doit appeler
`$user->forgetActiveSubscription()`**, ou passer par `$user->refresh()` qui le fait.
`SubscriptionService` s'en charge sur tous ses chemins d'écriture. Dans un test qui
traverse plusieurs requêtes simulées ou qui écrit en masse
(`$user->subscriptions()->update(...)`), relis l'instance : en production chaque requête
reconstruit son utilisateur depuis la session, pas un test qui en garde un seul.
`tests/Feature/Subscription/SubscriptionMemoTest.php` monte la garde.

## Analyse statique

`phpstan.neon` est écrit et le CI l'exécute — mais **larastan n'est pas encore
installé**, et c'est délibéré, pas un oubli. `phpstan/phpstan` n'est distribué
que par un zip de l'API GitHub (aucune `source` dans ses métadonnées), et cet
hôte est inaccessible depuis l'environnement où le dépôt a été préparé : ni
`--prefer-source`, ni `use-github-api false` ne contournent le problème,
puisqu'il n'y a rien d'autre à récupérer.

Sans larastan, PHPStan ne sait pas ce que rend `User::create()` ni quel type a
une colonne lue en propriété : il signale 404 « erreurs » sur ce dépôt, dont
aucune n'est un vrai défaut. Un baseline construit dans ces conditions serait
un mensonge figé.

Depuis une machine ayant accès à GitHub, deux commandes suffisent :

```bash
composer require --dev larastan/larastan
vendor/bin/phpstan analyse --generate-baseline
```

Le passage « Analyse statique » du CI s'allume alors tout seul : il est écrit
pour ne rien faire tant que `vendor/bin/phpstan` n'existe pas.

⚠️ **`APP_URL` est un réglage de sécurité, pas seulement de confort.** Laravel
compose ses URL absolues à partir de l'en-tête `Host` de la requête — et de
`X-Forwarded-Host`, honoré parce que `trustProxies(at: '*')` fait confiance à
tous les répartiteurs. C'est-à-dire à partir d'une valeur que le client choisit.
Une demande de mot de passe oublié envoyée avec un `Host` falsifié fait donc
partir, vers la boîte du titulaire, un lien de réinitialisation **valide qui
pointe chez l'attaquant**. `trustHosts` (`bootstrap/app.php`) filtre les noms
d'hôte à partir d'`APP_URL` ; un `APP_URL` vide vide la liste et éteint le
filtrage sans rien dire, d'où le contrôle dans `deploy:check`.
`tests/Feature/TrustedHostsTest.php` monte la garde.

⚠️ **N'écris jamais `where(..., 'like', ...)` à la main.** `like` est sensible à
la casse sur PostgreSQL et ne l'est pas sur MySQL ni SQLite : la recherche
renvoyait une page vide à qui tapait en minuscules, sans erreur nulle part et
sans qu'aucun test ne rougisse. Utilise `whereLike($colonne, $valeur,
caseSensitive: false)`, que Laravel compile en `ilike` sur PostgreSQL.
`tests/Feature/CaseInsensitiveSearchTest.php` monte la garde.

⚠️ `database/factories/ServiceCategoryFactory.php` tire ses noms dans un vivier
**volontairement disjoint** des noms que les tests posent en dur (« Plomberie »,
« Ménage », « Coiffure »…). `name` et `slug` sont uniques en base : un tirage au sort
inséré avant qu'un test n'impose le même nom fait échouer le test, de façon
intermittente. N'ajoute jamais au vivier un nom utilisé tel quel dans `tests/`.
