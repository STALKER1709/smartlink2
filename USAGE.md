# Guide d'utilisation

## Comptes de démonstration

Après `php artisan migrate --seed`, le `UserSeeder` crée trois comptes prêts à l'emploi (mot de passe : `password`) ainsi que des dizaines de clients/prestataires aléatoires :

| Rôle | Email | Mot de passe | Détails |
|---|---|---|---|
| Client | `client@smartlink.cm` | `password` | Aïcha Mballa, Douala |
| Prestataire | `provider@smartlink.cm` | `password` | Jean-Paul Eto'o, plombier vérifié à Douala |
| Administrateur | `admin@smartlink.cm` | `password` | Administrateur SmartLink |

## Parcours Visiteur (non connecté)

- **Accueil** (`/`) : présentation de SmartLink, catégories mises en avant, services récents.
- **Parcourir les services** (`/services`) : recherche par mot-clé, filtre par catégorie/ville, pagination.
- **Fiche service** (`/services/{slug}`) : détail, images, profil du prestataire, services similaires.
- **Parcourir les prestataires** (`/prestataires`) : liste avec filtre "vérifiés uniquement".
- **Fiche prestataire** (`/prestataires/{id}`) : profil, services proposés, avis clients.
- **Chatbot** : widget disponible sur tout le site, répond aux questions fréquentes (voir plus bas). Pour contacter un prestataire ou suivre une demande, le visiteur doit créer un compte.

## Parcours Client

1. **Inscription** (`/register`) en choisissant le rôle "Client" — crée automatiquement un profil client.
2. **Demander un service** : depuis une fiche service ou un profil prestataire, envoyer une demande (`/requests/create`) avec un message et, optionnellement, une date souhaitée.
3. **Suivre ses demandes** (`/requests`) : liste de toutes les demandes envoyées avec leur statut (voir cycle de vie ci-dessous).
4. **Messagerie** (`/conversations`) : une conversation est ouverte automatiquement dès qu'un prestataire accepte une demande ; le client peut alors échanger des messages avec lui.
5. **Notifications** (`/notifications`) : alertes lors d'un nouveau message ou d'un changement de statut sur une demande.
6. **Laisser un avis** : une fois une demande passée au statut "Terminée", le client peut noter (1 à 5) et commenter la prestation (`POST /requests/{id}/review`), une seule fois par demande.
7. **Profil** (`/client/profile`) : modifier prénom, nom, ville, localisation, photo.

## Parcours Prestataire

1. **Inscription** (`/register`) en choisissant le rôle "Prestataire" et un nom d'activité — crée automatiquement un profil prestataire.
2. **Compléter son profil** (`/provider/profile`) : nom de l'activité, catégorie, description, ville, zones d'intervention, horaires, moyens de contact, logo.
3. **Publier un service** (`/provider/services/create`) : titre, catégorie, description, prix indicatif (optionnel — purement informatif, SmartLink ne prélève rien dessus), ville, disponibilité, jusqu'à 5 photos. Le nombre de services publiables dépend du palier d'abonnement.
4. **Se faire aider à rédiger** : sur le formulaire de publication, décrivez votre métier en quelques mots — l'orthographe n'a pas d'importance — et un titre et une description vous sont proposés. Vous restez libre de tout modifier : c'est une proposition, pas une décision. Cette aide est comprise dans les paliers Essentiel et Pro.
5. **Gérer ses services** (`/provider/services`) : modifier, activer/désactiver, supprimer, ajouter ou retirer des photos.
6. **Traiter les demandes reçues** (`/requests`) : accepter ou refuser une demande, démarrer la prestation, puis la marquer comme terminée.
7. **Messagerie** (`/conversations`) : échanger avec le client une fois la demande acceptée.
8. **Suivre sa réputation** : la note moyenne et le nombre d'avis affichés sur son profil sont recalculés automatiquement à chaque nouvel avis.
9. **Gérer son abonnement** (`/provider/subscription`) : l'inscription ouvre 30 jours d'essai gratuit au niveau Pro. La page affiche le palier en cours, l'échéance, le nombre de services publiés et de demandes lues ce mois-ci, et le comparatif des paliers.
10. **Régler son abonnement** : choisir un palier, sélectionner MTN Mobile Money ou Orange Money, saisir son numéro. Une demande de confirmation arrive sur le téléphone ; composer son code Mobile Money valide le paiement. **Il n'existe aucun prélèvement automatique** — l'opérateur ne le permet pas — donc chaque cycle se renouvelle par une validation explicite, annoncée par SMS 3 jours puis 1 jour avant l'échéance.

### Cycle de vie d'une demande

```
brouillon → envoyée → (vue) → acceptée → en cours → terminée
                    ↘ refusée
        (à tout moment avant "terminée") → annulée
```

- Le client crée la demande (`envoyée`, ou `brouillon` s'il n'est pas encore prêt à l'envoyer).
- Le prestataire la consulte (`vue`), puis l'**accepte** ou la **refuse**. L'acceptation ouvre automatiquement une conversation.
- Une fois acceptée, le prestataire **démarre** la prestation (`en cours`) puis la **termine** (`terminée`).
- Le client ou le prestataire peut **annuler** une demande tant qu'elle n'a pas atteint un statut final (`terminée`, `refusée`, `annulée`).
- Seules les demandes `terminée` peuvent recevoir un avis du client.

## Parcours Administrateur

Accessible sur `/admin` (rôle `admin` requis) :

- **Utilisateurs** (`/admin/users`) : recherche/filtre par rôle, **suspendre** ou **réactiver** un compte (un administrateur ne peut pas se suspendre lui-même). Un compte suspendu est déconnecté immédiatement et ne peut plus se reconnecter tant qu'il n'est pas réactivé.
- **Services** (`/admin/services`) : vue d'ensemble, activer/désactiver ou supprimer un service en cas d'abus.
- **Catégories** (`/admin/categories`) : créer, modifier, supprimer les catégories de services (nom unique).

Chaque action de modération est enregistrée dans le journal d'audit (`AuditLogService`).

## Chercher un prestataire

Deux façons de chercher, sur la page `/services` :

**Décrire son besoin en une phrase.** « J'ai une fuite sous l'évier à Bonamoussadi » suffit : la catégorie, la ville, le quartier et le caractère urgent sont reconnus automatiquement, et les filtres se remplissent tout seuls. Un bandeau rappelle ce qui a été compris, et les filtres restent modifiables juste en dessous — la reconnaissance propose, vous corrigez.

**Filtrer précisément.** Catégorie, ville, quartier, mot-clé, disponibilité et tri restent accessibles directement, sans passer par la phrase.

La recherche par phrase est accessible **sans compte** : inutile de s'inscrire pour l'essayer. Les visiteurs non connectés disposent d'un nombre de recherches par jour ; au-delà, leur phrase est traitée comme un mot-clé ordinaire.

L'adresse obtenue après une recherche par phrase est une adresse de recherche classique : elle se partage, se met en favori et se rafraîchit sans surprise. Si la reconnaissance n'aboutit pas — pour une phrase trop vague, ou quand la fonction est momentanément indisponible — la phrase est traitée comme un simple mot-clé, sans message d'erreur.

## L'assistant

L'assistant répond aux questions sur la prise en main du site, la recherche d'un prestataire, l'envoi et le suivi d'une demande, la création de compte et les paliers d'abonnement. Interrogé sur le prix ou le paiement, il énonce le modèle réel : **SmartLink ne prélève rien sur les prestations**, le règlement se fait directement entre client et prestataire, et seuls les prestataires paient un abonnement mensuel à la plateforme.

Il connaît le catalogue réel — les catégories publiées, les villes où des prestataires sont effectivement visibles, les paliers et leurs prix — et répond dans la langue de la personne.

**Ce qu'il ne fait pas.** Il n'a accès à aucune donnée personnelle : ni votre compte, ni vos demandes, ni vos messages, ni vos paiements. À « où en est ma demande ? », il vous renvoie vers la page concernée plutôt que d'inventer une réponse. Il ne peut pas non plus agir à votre place : ni créer une demande, ni envoyer un message, ni modifier un abonnement.

**Deux modes.** Le mode par règles répond par mots-clés, sans appel externe ni coût. Le mode IA s'appuie sur le catalogue réel. La plateforme rabat automatiquement sur le mode par règles pour les visiteurs non connectés, au-delà du quota quotidien d'un compte, lorsque le plafond de dépense mensuel est atteint, ou si l'IA est momentanément injoignable. L'assistant répond donc toujours — il répond simplement moins finement.

## La modération

Les annonces et les avis passent par un examen automatique à la publication. Cet examen **signale, il ne supprime jamais** : un administrateur retrouve les contenus signalés sur `/admin/moderation` et décide seul de la suite. Classer un signalement sans suite laisse le contenu en ligne.

Un texte mal écrit, très court, en pidgin ou mêlant français et anglais n'est pas un motif de signalement — c'est la façon normale d'écrire de beaucoup de prestataires. Un avis négatif mais argumenté non plus. Sont visés les contenus qui poussent à traiter hors de la plateforme, les arnaques probables, les propos haineux, les coordonnées bancaires et les textes sans rapport avec une prestation.

## L'argent sur la plateforme

Un seul flux d'argent existe : **du prestataire vers SmartLink**, sous forme d'abonnement mensuel réglé en MTN Mobile Money ou Orange Money.

| Palier | Prix | Services publiés | Demandes lisibles / mois | Mise en avant | Rédaction IA | Statistiques |
|---|---|---|---|---|---|---|
| Essai (30 j) | gratuit | illimité | illimité | oui | oui | oui |
| Essentiel | 2 500 FCFA | 3 | 20 | non | oui | non |
| Pro | 7 500 FCFA | illimité | illimité | oui | oui | oui |

Aucune somme ne transite entre le client et le prestataire dans l'application : ni panier, ni acompte, ni facture, ni commission. Les prix affichés sur les services sont indicatifs et se règlent directement entre les deux parties, en dehors de la plateforme.

**À l'expiration**, les services du prestataire sortent des recherches. Son compte, ses demandes en cours et ses conversations restent accessibles — il peut honorer ce qu'il a déjà engagé. Le règlement de l'abonnement le fait réapparaître immédiatement.

**Plafond mensuel de demandes** : un prestataire au palier Essentiel qui atteint ses 20 demandes lisibles sort lui aussi des recherches jusqu'au mois suivant. C'est délibéré : sans cela, un client enverrait une demande que le prestataire ne pourrait pas lire.
