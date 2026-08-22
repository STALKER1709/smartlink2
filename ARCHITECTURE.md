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

### La couche IA

Le chatbot est défini par une interface unique :

```php
interface ChatbotProvider
{
    public function respond(string $message, array $history = [], ?User $user = null): string;
}
```

Deux implémentations coexistent en permanence. `RuleBasedChatbotProvider` répond par correspondance de mots-clés, sans dépendance externe ni coût. `ClaudeChatbotProvider` appelle l'API Claude via le SDK officiel `anthropic-ai/sdk`. Le pilote (`AI_DRIVER`) choisit laquelle le conteneur construit, mais **le mode par règles n'est jamais retiré** : `ChatbotService` y rabat à chaque refus et à chaque échec.

```
message
  → ChatbotService
      → AiGate : ce message a-t-il le droit de partir vers l'IA ?
          ↳ non  → RuleBasedChatbotProvider
          ↳ oui  → ClaudeChatbotProvider
                     ↳ exception → RuleBasedChatbotProvider
```

L'assistant est **informatif seul** : il ne consulte aucune donnée de compte et ne peut déclencher aucune action. Cela tient à deux choses — le prompt le lui interdit explicitement, et rien dans le code ne lui donne accès à autre chose que le catalogue public.

#### Ce que l'assistant sait

`SmartLinkContext` construit le prompt système à partir des données réelles : catégories actives, villes où des prestataires sont visibles, paliers et prix lus en base, cycle de vie d'une demande, pages de l'application. Il énonce surtout le modèle économique — c'est le point sur lequel l'ancien chatbot se trompait, et un test dédié vérifie que le contexte ne peut pas retomber dans l'erreur.

Le résultat est mis en cache une heure et **stable d'un appel à l'autre**, ce qui permet la mise en cache du prompt côté API (`cacheControl`) : seuls les messages varient, le contexte est facturé une fois puis relu à faible coût. Modifier un palier ou une catégorie invalide le cache immédiatement, via les événements de modèle.

#### Les garde-fous de coût

`AiGate` tranche appel par appel, dans cet ordre :

1. **Pilote** — `AI_DRIVER` différent de `claude` : rien ne part.
2. **Clé** — clé d'API absente : rien ne part, sans erreur pour l'utilisateur.
3. **Visiteur anonyme** — `AI_REQUIRE_AUTH` renvoie les visiteurs au mode par règles ; la dépense devient un motif d'inscription.
4. **Plafond mensuel** — au-delà de `AI_MONTHLY_BUDGET_USD`, toute la plateforme bascule en mode par règles, avec alerte dans les journaux.
5. **Quota quotidien** — au-delà de `AI_DAILY_MESSAGES` messages, ce compte-là seulement bascule.

`AiUsageRecorder` enregistre chaque appel dans `ai_usages` — fonction, modèle, jetons, coût calculé d'après la grille tarifaire de `config/ai.php`. C'est cette table qui alimente les deux derniers garde-fous.

Un modèle par tâche : le plus capable pour ce qu'un humain lit (conversation, rédaction), le plus économique pour l'extraction et le classement en volume (recherche, modération).

#### La recherche en langage naturel

`SearchIntentExtractor` traduit une phrase libre — « J'ai une fuite sous l'évier à Bonamoussadi » — en filtres de recherche classiques : catégorie, ville, quartier, mots-clés, urgence. Il s'appuie sur les **sorties structurées** de l'API : le schéma contraint la catégorie et la ville à des valeurs prises dans le catalogue réel, ce qui écarte d'emblée la plupart des inventions.

La contrainte de schéma ne suffit pas à faire confiance. `toIntent()` est la **barrière** : chaque champ est revérifié contre la base, une catégorie qui ne correspond à aucune ligne est abandonnée plutôt que propagée, et les champs libres sont tronqués. C'est cette méthode qui est publique et testée directement, parce que c'est par elle que la sortie du modèle entre dans l'application.

Le contrôleur **redirige** ensuite vers la recherche classique avec les paramètres résolus, plutôt que d'afficher les résultats directement. Ce détour a trois effets : l'URL obtenue est partageable, les filtres restent visibles et modifiables — l'IA propose, l'utilisateur corrige — et un rafraîchissement de page ne refacture pas d'extraction.

Tout échec ramène silencieusement à la recherche par mot-clé : IA coupée, plafond atteint, visiteur anonyme, extraction inexploitable ou intention vide. L'utilisateur obtient des résultats dans tous les cas, simplement moins bien ciblés.

Le quota quotidien par compte ne borne que la conversation : sans cela, un utilisateur bavard se retrouverait privé de recherche. L'extraction reste bornée par le plafond de dépense mensuel, et elle s'appuie sur le modèle le plus économique.

#### L'historique vient du navigateur

`ConversationHistory` remet en état l'historique envoyé par le client avant de le transmettre : rôles inconnus écartés, contenus vides ou non textuels écartés, tours consécutifs du même rôle dédoublonnés, conversation tronquée aux derniers tours et forcée à commencer par un tour utilisateur. Rien de ce qui vient du navigateur n'est transmis tel quel.

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

### Visibilité : un booléen dénormalisé

Un prestataire n'apparaît dans les recherches que si son abonnement est en cours **et** que son plafond mensuel de demandes n'est pas atteint. Évaluer cette condition à chaque recherche obligerait à corréler l'abonnement, son palier et un décompte mensuel : elle est donc dénormalisée en deux colonnes sur `provider_profiles`, `is_listed` (visible) et `is_promoted` (mis en avant par le palier Pro), recalculées par `QuotaService::refreshListing()`.

Ce recalcul est déclenché à l'ouverture de l'essai, au renouvellement, à chaque demande nouvellement lue, et une fois par jour par la commande `subscriptions:refresh` — c'est ce passage quotidien qui fait réapparaître, au changement de mois, les prestataires qui étaient au plafond. Le compteur mensuel lui-même (`requests_read_count` / `requests_read_period`) se remet à zéro tout seul : il ne compte que si la période enregistrée est le mois courant.

Les recherches ne filtrent plus que sur ce booléen indexé (`Service::scopeFromListedProvider()`, `ProviderProfile::scopeListed()`). Les fiches publiques d'un prestataire masqué répondent 404 pour les visiteurs et les clients — sans quoi un lien direct laisserait envoyer une demande qui ne serait jamais lue — mais restent accessibles au prestataire lui-même et aux administrateurs.

### Ce que le palier autorise

`QuotaService` est le point d'entrée unique pour « qu'ai-je le droit de faire en ce moment » : publier un service de plus, lire une nouvelle demande, combien il en reste. Deux règles encadrent l'expiration :

- **Publier** exige un abonnement en cours (`ServicePolicy::create`). Modifier ou supprimer un service déjà publié reste toujours permis.
- **Lire une demande** ne consomme un point de quota qu'à la première ouverture, quand la demande passe de « envoyée » à « vue ». Une demande déjà ouverte reste lisible indéfiniment, quel que soit l'état de l'abonnement : ce qui est engagé le reste.

`Subscription::isUsable()` est le point de vérité unique : un abonnement ouvre des droits s'il est en essai ou actif **et** que son échéance est devant. Dès qu'il ne l'est plus, `User::activeSubscription()` renvoie `null` et le prestataire disparaît des recherches, sans que son compte ni ses conversations soient touchés.

### Renouvellement : manuel par nature

L'API Campay expose `collect`, `initCollect`, `get_transaction_status`, `get_payment_link`, `disburse` et `get_balance` — **aucun mandat récurrent, aucun prélèvement sans validation du payeur**. Chaque cycle exige donc que le prestataire compose son code sur son téléphone. Le renouvellement est **annoncé par SMS avant l'échéance** (`config/subscription.php`, clé `reminder_days`), puis déclenché par le prestataire lui-même.

Le parcours de règlement suit trois temps :

1. `SubscriptionService::requestPayment()` crée un `Payment` en attente portant le palier visé, puis demande la collecte à Campay. Une collecte déjà en attente **pour le même palier** est réutilisée plutôt que relancée : sans cela, un double clic ferait payer deux fois. Un changement de palier en cours de route abandonne la collecte précédente au lieu de la réutiliser avec le mauvais montant.
2. Campay répond presque toujours `PENDING` : le prestataire n'a pas encore validé. Le palier visé n'est **pas** appliqué à ce stade — un changement de formule ne prend effet qu'au règlement abouti.
3. `PaymentController::webhook()` reçoit la confirmation de l'opérateur, seul canal qui fait foi. Il est exempté de la vérification CSRF dans `bootstrap/app.php` — un rappel externe ne peut pas porter de jeton — la légitimité étant vérifiée par la signature partagée.

`CampayService::collect()` normalise la réponse de l'opérateur : une collecte acceptée renvoie une référence sans champ de statut, et la traiter comme un échec ferait échouer tout paiement réel.

### Le passage quotidien

`subscriptions:refresh` (planifié à 2 h) enchaîne trois tâches dans cet ordre :

1. **Relances** — un SMS par seuil franchi (`reminder_days`, par défaut 3 jours puis 1 jour avant l'échéance). `last_reminder_day` sur l'abonnement retient le dernier seuil envoyé, ce qui évite de renvoyer le même message à chaque passage. À un jour restant, les seuils 3 et 1 sont tous deux franchis : c'est le plus proche de l'échéance qui vaut.
2. **Expiration** — les abonnements échus basculent en `expired`, avec journal d'audit et SMS de notification. C'est le moment où les services sortent des recherches.
3. **Visibilité** — recalcul de `is_listed` / `is_promoted` pour tout le monde, ce qui fait notamment réapparaître au changement de mois les prestataires qui étaient au plafond de demandes.

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
