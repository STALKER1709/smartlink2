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

- Backend : Laravel 13 (PHP 8.3+), MySQL en prod / SQLite en dev-tests
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
composer install    # composer.lock est gitignored : régénère-le localement si besoin
php artisan view:clear   # voir ci-dessous — indispensable après un changement de couleurs
npm install && npm run build
php artisan test
vendor/bin/pint --test
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

⚠️ `database/factories/ServiceCategoryFactory.php` tire ses noms dans un vivier
**volontairement disjoint** des noms que les tests posent en dur (« Plomberie »,
« Ménage », « Coiffure »…). `name` et `slug` sont uniques en base : un tirage au sort
inséré avant qu'un test n'impose le même nom fait échouer le test, de façon
intermittente. N'ajoute jamais au vivier un nom utilisé tel quel dans `tests/`.
