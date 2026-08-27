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

## Sept propositions Wikimedia Commons, à relire

`config/imagery.php` porte sept entrées pointant vers des fichiers de Wikimedia
Commons — plomberie, électricité, coiffure, couture, maçonnerie, mécanique,
vente de vivres. Ce sont des **propositions, pas un choix** : leurs titres
viennent de l'index de Commons, donc les fichiers existent, mais personne ne
les a regardés et leurs mentions d'auteur sont vides.

```bash
php artisan images:credits    # relève auteur et licence sur l'API de Commons
# coller le bloc rendu dans config/imagery.php
php artisan images:check      # vérifie que tout répond et que rien ne manque
```

Tant qu'une entrée n'a pas d'auteur **et** de licence, elle ne s'affiche pas —
même avec `REMOTE_IMAGES=true`. Ouvrez les sept pages source, regardez les
photos, gardez celles qui vous conviennent.

Sept métiers sur trente et un : une grille où sept vignettes sont
photographiques et vingt-quatre dessinées se lit comme un défaut. Complétez la
table, ou laissez le commutateur à faux.

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
