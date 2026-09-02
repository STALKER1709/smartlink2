# Photographies réelles

Ce dossier est l'entrée des photographies destinées aux illustrations de
couverture. Déposez-les ici, puis lancez :

```bash
php artisan photos:import
```

La commande copie les fichiers vers le disque de médias (`media_disk()`, donc
S3 en production comme en local) et les attribue aux services de la catégorie
correspondante. Les illustrations générées ne servent que de repli : dès qu'une
photographie existe pour une catégorie, c'est elle qui s'affiche.

## Nommage

Le nom du fichier porte la catégorie et un numéro :

```
plomberie-1.jpg
plomberie-2.jpg
coiffure-1.jpg
menuiserie-1.jpg
```

Les clés reconnues sont celles de `database/seeders/data/images/categories.json`.
`php artisan photos:import --list` les affiche, avec le nombre de photos déjà
déposées pour chacune.

## Aller les chercher

Le script `fetch.mjs` interroge les banques d'images libres, nomme les fichiers
comme il faut et inscrit leur provenance dans `SOURCES.md` :

```bash
node design/photos/fetch.mjs --liste            # les clés et les requêtes employées
node design/photos/fetch.mjs --simuler          # ce qu'il prendrait, sans rien écrire
node design/photos/fetch.mjs --par 2            # 2 photos par catégorie, via Openverse
PEXELS_API_KEY=xxx node design/photos/fetch.mjs --source pexels --par 3
node design/photos/fetch.mjs --cle coiffure --par 4   # une seule catégorie
```

Il ne retélécharge jamais ce qui est déjà là : relancé, il complète. Les
banques retenues et leurs licences sont dans `SOURCES.md`.

**Relisez les images une par une avant d'importer.** Une requête rend toujours
quelques hors-sujet, et une photo hors sujet vaut moins que l'illustration
générée qu'elle remplace.

## Ce qu'il faut viser

- **Format paysage**, 1200 × 900 px au minimum. Les vignettes sont carrées et
  les fiches en 16/9 : une photo verticale sera recadrée sans ménagement.
- **JPEG**, sous 500 Ko pièce. Les visiteurs sont sur un Android de milieu de
  gamme en 3G ; une photo de 4 Mo coûte plus qu'elle ne rapporte.
- **Des scènes camerounaises.** Une photo de stock d'un plombier européen dans
  une cuisine européenne dessert une place de marché camerounaise — c'est la
  raison pour laquelle les illustrations actuelles ont été dessinées plutôt que
  cherchées en ligne.
- **Des droits vérifiés.** Photo prise par vous, commandée par vous, ou sous une
  licence qui autorise l'usage commercial sans attribution. Une image trouvée
  dans un moteur de recherche n'est aucun des trois. Notez la provenance de
  chaque fichier dans `SOURCES.md` à côté : le jour où quelqu'un la demande,
  personne ne s'en souviendra.

## Où en est la plateforme aujourd'hui

`config/imagery.php` est rempli — trente et un métiers et le bandeau d'appel
aux prestataires — avec des **images de calage** servies par LoremFlickr, et
`REMOTE_IMAGES` est à vrai. Les pages ont donc des photographies, choisies sur
mots-clés, ce qui fait qu'une vignette de plomberie montre de la plomberie.

Trois réserves, qui ne s'effacent pas parce qu'on les a lues :

- la licence Creative Commons de la photo servie réclame le nom de son auteur,
  que l'hôte ne fait pas connaître à l'avance — la mention portée dans les
  mentions légales est donc une mention de classe, pas la mention individuelle
  que la licence demande ;
- l'hôte peut changer la photo d'un jour à l'autre ;
- chaque affichage envoie l'adresse IP du visiteur à un tiers.

C'est tenable pour montrer une maquette qui tient debout, pas pour une mise en
ligne. `REMOTE_IMAGES=false` coupe tout et rend la main aux illustrations
dessinées, qui appartiennent au projet.

## La photo de métier

`config/imagery.php` porte une photographie par métier — celle qui paraît sur
la vignette de chaque service et sur les cartes de l'accueil. Le chemin
complet, du téléchargement à l'affichage :

```bash
node design/photos/fetch.mjs --par 1     # rapatrie, et note la provenance
php artisan photos:import --config       # dépose, puis écrit le bloc à coller
# coller le bloc dans config/imagery.php, poser REMOTE_IMAGES=true
php artisan images:check                 # vérifie les URL et les mentions
```

Chaque entrée porte l'auteur et la licence en plus de l'URL, et ce n'est pas
décoratif : les mentions sont rendues dans les mentions légales, et
`images:check` refuse une URL qui n'en a pas. Presque toutes les licences
libres l'exigent ; une photo dont vous détenez les droits se déclare de la
même façon, à votre nom.

`REMOTE_IMAGES=false` coupe tout : les illustrations dessinées reprennent la
main. C'est la valeur par défaut, tant que la table est vide — une page à
moitié photographique et à moitié dessinée se lit comme un défaut.

Les trois couches se recouvrent dans cet ordre, de la moins bonne à la
meilleure : illustration dessinée du métier, photographie de métier,
photographie déposée par le prestataire sur son propre service. Chacune
s'efface d'elle-même si elle échoue, ce qui laisse toujours quelque chose à
voir.

## Ce que la commande ne fait pas

Elle ne redimensionne ni ne recompresse : ce que vous déposez est ce qui part en
ligne. Préparez les fichiers avant.
