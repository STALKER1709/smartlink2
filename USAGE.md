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
3. **Publier un service** (`/provider/services/create`) : titre, catégorie, description, prix indicatif (optionnel — purement informatif, aucun paiement n'est traité par SmartLink), ville, disponibilité, jusqu'à 5 photos.
4. **Gérer ses services** (`/provider/services`) : modifier, activer/désactiver, supprimer, ajouter ou retirer des photos.
5. **Traiter les demandes reçues** (`/requests`) : accepter ou refuser une demande, démarrer la prestation, puis la marquer comme terminée.
6. **Messagerie** (`/conversations`) : échanger avec le client une fois la demande acceptée.
7. **Suivre sa réputation** : la note moyenne et le nombre d'avis affichés sur son profil sont recalculés automatiquement à chaque nouvel avis.

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

## Le chatbot (FAQ automatisée)

Le chatbot répond par mots-clés (sans dépendance externe) aux questions sur : la prise en main du site, la recherche d'un prestataire, l'envoi d'une demande, le suivi d'une demande, la création de compte, et les remerciements. Il rappelle explicitement, si on l'interroge sur le prix ou le paiement, que **SmartLink ne gère aucun paiement en ligne** et que les échanges financiers se font hors plateforme.

## Rappel : aucun paiement sur la plateforme

SmartLink est un outil de mise en relation. Il n'y a pas de panier, pas de passerelle de paiement, pas de facture, pas d'historique de transaction. Les champs de prix affichés sur les services sont indicatifs ; le règlement du service se négocie et s'effectue directement entre le client et le prestataire, en dehors de l'application.
