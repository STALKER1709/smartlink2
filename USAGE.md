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
4. **Gérer ses services** (`/provider/services`) : modifier, activer/désactiver, supprimer, ajouter ou retirer des photos.
5. **Traiter les demandes reçues** (`/requests`) : accepter ou refuser une demande, démarrer la prestation, puis la marquer comme terminée.
6. **Messagerie** (`/conversations`) : échanger avec le client une fois la demande acceptée.
7. **Suivre sa réputation** : la note moyenne et le nombre d'avis affichés sur son profil sont recalculés automatiquement à chaque nouvel avis.
8. **Gérer son abonnement** : l'inscription ouvre 30 jours d'essai gratuit au niveau Pro. Avant l'échéance, des SMS de relance invitent à choisir un palier et à le régler en Mobile Money.

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

## L'assistant

L'assistant répond aux questions sur la prise en main du site, la recherche d'un prestataire, l'envoi et le suivi d'une demande, la création de compte et les paliers d'abonnement. Interrogé sur le prix ou le paiement, il énonce le modèle réel : **SmartLink ne prélève rien sur les prestations**, le règlement se fait directement entre client et prestataire, et seuls les prestataires paient un abonnement mensuel à la plateforme.

Deux modes coexistent. Le mode par règles répond par correspondance de mots-clés, sans appel externe ni coût. Le mode IA, quand il est activé, s'appuie sur le catalogue réel. La plateforme rabat automatiquement sur le mode par règles pour les visiteurs non connectés, au-delà du quota quotidien d'un compte, ou lorsque le plafond de dépense mensuel est atteint.

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
