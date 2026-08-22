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
| `Plan` | Palier commercial et ses limites (services, demandes mensuelles, mise en avant, IA) |
| `Subscription` | Abonnement d'un prestataire à un palier, avec statut et échéance |
| `Payment` | Règlement Mobile Money d'un abonnement (aucun lien avec une prestation) |
| `AiUsage` | Consommation IA par appel : fonction, modèle, jetons, coût — base des garde-fous |

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

L'implémentation par défaut, `RuleBasedChatbotProvider`, répond par correspondance de mots-clés (sans dépendance externe ni coût d'API). Elle reste en place en permanence : c'est le mode vers lequel la plateforme rabat dès qu'un garde-fou de coût se déclenche (visiteur anonyme, quota quotidien atteint, plafond mensuel dépassé). Le binding se fait dans `AppServiceProvider` :

```php
$this->app->bind(ChatbotProvider::class, RuleBasedChatbotProvider::class);
```

Pour brancher un vrai service d'IA, il suffit d'écrire une nouvelle classe implémentant `ChatbotProvider` et de changer ce binding — aucun autre code de l'application n'a besoin d'être modifié. La configuration vit dans `config/ai.php` : pilote, clé d'API, modèle par tâche, tarifs servant au calcul du coût, et garde-fous (authentification requise, quota quotidien par compte, plafond mensuel en dollars).

Quel que soit le driver, l'assistant doit décrire fidèlement le modèle économique : SmartLink ne prélève rien sur les prestations, et seuls les prestataires paient un abonnement. C'est vérifié par des tests dédiés (`ChatbotTest`, `RuleBasedChatbotProviderTest`).

## Autorisation : middleware + policies

- **Middleware `role:<role1>,<role2>,...`** (`EnsureUserHasRole`) protège des groupes entiers de routes (`/provider/*`, `/client/*`, `/admin/*`) en comparant `$user->role`. C'est la première ligne de défense, appliquée au niveau des routes.
- **`EnsureAccountIsActive`**, ajouté au groupe `web`, déconnecte immédiatement tout utilisateur dont le compte passe à `suspended` (vérifié à chaque requête).
- **Policies Eloquent** (`app/Policies`) gèrent les autorisations fines au niveau d'une ressource précise : un prestataire ne peut modifier que **ses** services (`ServicePolicy::update`), un client ne peut laisser un avis que sur **sa** demande **terminée** (`ReviewPolicy`), un administrateur ne peut pas se suspendre lui-même (`UserPolicy::suspend`), etc. Les contrôleurs appellent `$this->authorize(...)`.

Cette séparation signifie qu'une requête peut échouer à deux niveaux différents : 403 du middleware (mauvais rôle) avant même d'atteindre le contrôleur, ou 403 de la policy (bon rôle, mais pas le bon propriétaire) une fois dans le contrôleur.

## Formulaires et validation

Chaque action d'écriture a son propre `FormRequest` (`app/Http/Requests/...`), responsable uniquement des règles de validation — l'autorisation est gérée séparément par les policies, pas dans `authorize()` des Form Requests (qui renvoient `true`). Cette séparation évite de dupliquer la logique d'autorisation à deux endroits.

## Stockage des fichiers

Logos, photos de profil et images de service sont stockés sur le disque `public` (`storage/app/public`, exposé via `php artisan storage:link`). Les contrôleurs suppriment l'ancien fichier avant d'enregistrer le nouveau (logo, photo) ou suppriment le fichier physique en même temps que l'enregistrement `ServiceImage` (retrait d'une image de service).

## Modèle économique : un seul flux d'argent

SmartLink se finance par l'abonnement des prestataires. C'est le **seul** mouvement d'argent de l'application, et il va du prestataire vers la plateforme.

Il n'existe dans ce projet :
- aucun panier, aucun acompte, aucune facture de prestation ;
- aucun reversement d'argent d'un utilisateur vers un autre ;
- aucune commission prélevée sur le montant d'une prestation.

Le champ `price_amount` sur `Service` reste purement informatif : il affiche un tarif indicatif. Le règlement de la prestation se convient et s'effectue directement entre le client et le prestataire, hors plateforme. Cette contrainte est respectée dans le schéma de base de données, dans les vues, dans les réponses du chatbot, et couverte par des tests automatisés.

### Abonnements

Trois tables portent le modèle :

- **`plans`** — les paliers commerciaux (`essential`, `pro`) et leurs limites : nombre de services publiables, nombre de demandes lisibles par mois, mise en avant, accès à la rédaction assistée, statistiques. Les prix sont stockés en base et modifiables sans redéploiement ; les libellés viennent des fichiers de langue, ce qui préserve le bilinguisme.
- **`subscriptions`** — l'abonnement d'un prestataire à un palier, avec son statut (`trialing`, `active`, `expired`, `cancelled`) et sa date d'échéance. L'essai gratuit n'est pas un palier distinct : c'est un abonnement au palier `pro` en statut `trialing`, ce qui évite de dupliquer la logique de droits.
- **`payments`** — les règlements Mobile Money d'un abonnement. La table ne référence plus de demande de service : elle appartient à une `Subscription`.

`Subscription::isUsable()` est le point de vérité unique : un abonnement ouvre des droits s'il est en essai ou actif **et** que son échéance est devant. Dès qu'il ne l'est plus, `User::activeSubscription()` renvoie `null` et le prestataire disparaît des recherches, sans que son compte ni ses conversations soient touchés.

### Renouvellement : manuel par nature

Le Mobile Money camerounais n'autorise pas le prélèvement automatique : chaque cycle exige une validation du prestataire sur son téléphone. Le renouvellement est donc **annoncé par SMS avant l'échéance** (`config/subscription.php`, clé `reminder_days`), puis déclenché par le prestataire lui-même. `PaymentController::webhook()` est le seul canal qui confirme un règlement — d'où son exemption CSRF explicite dans `bootstrap/app.php`, compensée par la vérification de signature partagée.

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
