# Architecture

## Vue d'ensemble

SmartLink suit une architecture **MVC stricte** fournie par Laravel, avec une fine couche de **services métier** entre les contrôleurs et les modèles Eloquent. L'objectif est de garder les contrôleurs minces (validation + délégation + réponse HTTP) et de concentrer les règles métier (transitions de statut, recalcul de note, journalisation) dans des classes dédiées et testables.

```
Requête HTTP
   → Route (routes/web.php)
   → Middleware (auth, role:, EnsureAccountIsActive)
   → Form Request (validation)
   → Controller (autorise via Policy, délègue au Service si besoin)
   → Service métier (RequestService, NotificationService, AuditLogService, SearchService, ChatbotService)
   → Modèle Eloquent / base de données
   → Vue Blade (ou redirection)
```

## Modèles et relations

| Modèle | Rôle |
|---|---|
| `User` | Compte unique pour les 4 rôles humains (`role` : `client`\|`provider`\|`admin`), avec `status` (`active`\|`suspended`) |
| `ClientProfile` / `ProviderProfile` | Profil métier 1-1 avec `User`, créé automatiquement à l'inscription |
| `ServiceCategory` | Catégorie de service (plomberie, ménage, etc.) |
| `Service` / `ServiceImage` | Service publié par un prestataire, avec jusqu'à 5 images |
| `ServiceRequest` (table `requests`) | Demande d'un client à un prestataire, avec machine à états (voir [USAGE.md](USAGE.md)) |
| `RequestStatusHistory` | Historique horodaté de chaque transition de statut d'une demande |
| `Conversation` / `Message` | Messagerie interne, ouverte automatiquement quand une demande est acceptée |
| `Review` | Avis client (note 1-5 + commentaire), un par demande **terminée**, unique (`request_id` unique) |
| `Setting` | Paramètres clé/valeur de l'application |
| `AuditLog` | Journal des actions sensibles (suspension, modération, changements de statut) |

Les profils (`ClientProfile`/`ProviderProfile`) sont créés systématiquement à l'inscription par `RegisteredUserController` — c'est un invariant de l'application : un compte client ou prestataire a toujours un profil associé.

## Couche services (`app/Services`)

- **`RequestService`** — seul point d'entrée pour faire transitionner une `ServiceRequest`. Une table de transitions autorisées (`STATUS_DRAFT → STATUS_SENT`, etc.) est vérifiée avant tout changement ; toute transition invalide lève `InvalidRequestTransitionException`. Chaque transition est enregistrée dans `RequestStatusHistory`, journalisée via `AuditLogService`, et déclenche une notification via `NotificationService`. L'acceptation d'une demande crée automatiquement la `Conversation` associée.
- **`NotificationService`** — centralise l'envoi des notifications Laravel (`NewRequestNotification`, `RequestStatusChangedNotification`, `NewMessageNotification`, `NewReviewNotification`), toutes persistées en base (`via() => ['database']`) et consultables sur `/notifications`.
- **`SearchService`** — requêtes de recherche/filtrage pour les services (catégorie, ville, mot-clé, tri par prix) et les prestataires (catégorie, ville, vérifié, mot-clé), utilisées par les pages publiques.
- **`AuditLogService`** — enregistre qui a fait quoi sur quelle ressource (`user_id`, `action`, `auditable_type/id`, `metadata`, `ip_address`), utilisée par les actions d'administration et `RequestService`.
- **`ChatbotService`** — délègue à une implémentation de `App\Contracts\ChatbotProvider`.

### Abstraction du chatbot

Le chatbot est défini par une interface unique :

```php
interface ChatbotProvider
{
    public function respond(string $message, array $history = []): string;
}
```

L'implémentation par défaut, `RuleBasedChatbotProvider`, répond par correspondance de mots-clés (sans dépendance externe ni coût d'API). Le binding se fait dans `AppServiceProvider` :

```php
$this->app->bind(ChatbotProvider::class, RuleBasedChatbotProvider::class);
```

Pour brancher un vrai service d'IA (OpenAI, Anthropic, etc.), il suffit d'écrire une nouvelle classe implémentant `ChatbotProvider` et de changer ce binding — aucun autre code de l'application n'a besoin d'être modifié. `CHATBOT_DRIVER` dans `.env.example` documente cette intention mais n'est pas encore lu dynamiquement : le choix se fait aujourd'hui au niveau du binding, pas par variable d'environnement.

Quel que soit le driver, le chatbot ne doit jamais évoquer de paiement en ligne : c'est vérifié par un test dédié (`ChatbotTest::test_chatbot_never_mentions_online_payment_processing`).

## Autorisation : middleware + policies

- **Middleware `role:<role1>,<role2>,...`** (`EnsureUserHasRole`) protège des groupes entiers de routes (`/provider/*`, `/client/*`, `/admin/*`) en comparant `$user->role`. C'est la première ligne de défense, appliquée au niveau des routes.
- **`EnsureAccountIsActive`**, ajouté au groupe `web`, déconnecte immédiatement tout utilisateur dont le compte passe à `suspended` (vérifié à chaque requête).
- **Policies Eloquent** (`app/Policies`) gèrent les autorisations fines au niveau d'une ressource précise : un prestataire ne peut modifier que **ses** services (`ServicePolicy::update`), un client ne peut laisser un avis que sur **sa** demande **terminée** (`ReviewPolicy`), un administrateur ne peut pas se suspendre lui-même (`UserPolicy::suspend`), etc. Les contrôleurs appellent `$this->authorize(...)`.

Cette séparation signifie qu'une requête peut échouer à deux niveaux différents : 403 du middleware (mauvais rôle) avant même d'atteindre le contrôleur, ou 403 de la policy (bon rôle, mais pas le bon propriétaire) une fois dans le contrôleur.

## Formulaires et validation

Chaque action d'écriture a son propre `FormRequest` (`app/Http/Requests/...`), responsable uniquement des règles de validation — l'autorisation est gérée séparément par les policies, pas dans `authorize()` des Form Requests (qui renvoient `true`). Cette séparation évite de dupliquer la logique d'autorisation à deux endroits.

## Stockage des fichiers

Logos, photos de profil et images de service sont stockés sur le disque `public` (`storage/app/public`, exposé via `php artisan storage:link`). Les contrôleurs suppriment l'ancien fichier avant d'enregistrer le nouveau (logo, photo) ou suppriment le fichier physique en même temps que l'enregistrement `ServiceImage` (retrait d'une image de service).

## Aucun paiement : un choix d'architecture, pas une omission

Il n'existe dans ce projet :
- aucune table de transaction, facture ou paiement ;
- aucune route, contrôleur ou vue de type *checkout* ;
- aucune intégration de passerelle de paiement (Stripe, Orange Money, MTN Mobile Money, etc.).

Le champ `price_amount` sur `Service` est purement informatif (affichage d'un tarif indicatif). La mise en relation s'arrête à l'organisation de la prestation ; le règlement se fait hors plateforme, entre le client et le prestataire. C'est une contrainte fonctionnelle du produit, respectée à la fois dans le schéma de base de données, dans les vues, et dans les réponses du chatbot — et couverte par un test automatisé.

## Tests (`tests/`)

- **`tests/Unit`** — modèles (`ConversationTest`, `ServiceRequestTest`, `UserTest`) et services métier isolés (`RequestServiceTest`, `AuditLogServiceTest`, `RuleBasedChatbotProviderTest`), sans passer par le HTTP.
- **`tests/Feature`** — comportement bout-en-bout par parcours : navigation publique (`HomeTest`, `ServiceBrowsingTest`, `ProviderBrowsingTest`), cycle de vie complet d'une demande (`ServiceRequestLifecycleTest`), messagerie (`MessagingTest`), notifications (`NotificationTest`), avis (`ReviewTest`), chatbot (`ChatbotTest`), tableau de bord (`DashboardTest`), gestion par le prestataire de ses services et de son profil (`Provider/`), gestion par le client de son profil (`Client/`), modération par l'administrateur (`Admin/`).

Chaque test Feature vérifie systématiquement le cas autorisé **et** le cas refusé (invité redirigé, mauvais rôle → 403, propriétaire incorrect → 403), conformément au modèle d'autorisation à deux niveaux décrit plus haut.

```bash
php artisan test
```

## Style de code

Le style est uniformisé avec [Laravel Pint](https://laravel.com/docs/pint) (préréglage Laravel) :

```bash
vendor/bin/pint --test   # vérification
vendor/bin/pint          # correction automatique
```
