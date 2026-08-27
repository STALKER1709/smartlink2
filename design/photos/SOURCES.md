# Provenance des photographies

Une ligne par fichier déposé dans ce dossier. Sans elle, la question des droits
se pose au pire moment — quand quelqu'un la soulève.

`node design/photos/fetch.mjs` écrit sa ligne lui-même, au moment où il écrit le
fichier. Les photos déposées à la main se notent à la main.

| Fichier | Provenance | Licence | Date |
|---|---|---|---|
| _(exemple)_ `plomberie-1.jpg` | Photo prise par l'équipe, Douala | Propriété SmartLink | 2026-08 |

## Banques d'images retenues

Ce tableau ne dispense pas de lire la page de licence : il dit seulement
pourquoi ces quatre-là ont été retenues et les autres écartées.

| Banque | API | Usage commercial | Attribution | Réserves connues |
|---|---|---|---|---|
| [Pexels](https://www.pexels.com/license/) | oui, clé gratuite | oui | non exigée | ne pas revendre la photo inchangée ; ne pas la reverser sur une autre banque ; pas de personne identifiable présentée défavorablement ; pas d'endossement suggéré |
| [Unsplash](https://unsplash.com/license) | oui, clé gratuite | oui | non exigée | ne pas constituer un service concurrent d'Unsplash à partir de son fonds |
| [Openverse](https://openverse.org/) | oui, sans clé | selon le fichier | **selon le fichier** | agrégateur : la licence change d'une image à l'autre. `fetch.mjs` s'y limite à CC0 et domaine public, faute d'endroit où poser une attribution |
| [Nappy](https://nappy.co/) · [Iwaria](https://iwaria.com/) · [PICHA](https://pichastock.com/) | non | annoncé oui | annoncée non exigée | téléchargement manuel. Ce sont les fonds les plus pertinents ici — photographes africains, scènes africaines — mais chaque fichier a sa page : vérifiez-la avant de déposer |

Écartés : Freepik (licence gratuite conditionnée à une attribution),
Shutterstock et Dreamstime (payants), et tout résultat d'un moteur de recherche
d'images — une vignette trouvée dans Google n'est accompagnée d'aucun droit.

Termes relevés le 2026-08-27 à partir de la documentation publique de ces
plateformes. Ils changent ; la page de licence fait foi, pas ce tableau.

## La réserve qui compte plus que la licence

Ces fonds sont massivement européens et nord-américains. Une photo de plombier
techniquement libre de droits, prise dans une cuisine berlinoise, dessert une
place de marché camerounaise plus qu'elle ne la sert — c'est la raison pour
laquelle les illustrations actuelles ont été dessinées plutôt que cherchées.
Les requêtes de `fetch.mjs` orientent la recherche, elles ne la garantissent
pas : relisez chaque image avant `php artisan photos:import`, et préférez une
illustration générée à une photo hors sujet.
