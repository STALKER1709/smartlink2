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

## Ce que la commande ne fait pas

Elle ne redimensionne ni ne recompresse : ce que vous déposez est ce qui part en
ligne. Préparez les fichiers avant.
