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

⚠️ Les fichiers `README.md` / `ARCHITECTURE.md` / `USAGE.md` d'origine décrivaient un
produit **sans aucun paiement en ligne**. Ce n'est plus vrai : le projet a évolué vers un
modèle avec acompte et abonnement prestataire payés en Mobile Money (le fournisseur est
passé de Campay à **HR-Skills Pay**), plus un assistant IA (recherche en langage
naturel, aide à la rédaction, modération automatique) basé sur `anthropic-ai/sdk`. Cette
doc n'a pas encore été remise à jour pour refléter ce changement — ne te fie pas
aveuglément à son contenu concernant le paiement, vérifie plutôt le code
(`app/Services`, `app/Http/Controllers`, `routes/web.php`).

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
npm install && npm run build
php artisan test
vendor/bin/pint --test
```

## Setup local rapide

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # ou configurer MySQL comme dans .env.example
php artisan migrate --seed
npm run build
```
