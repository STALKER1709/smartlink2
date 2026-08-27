# Imports Google Stitch

Ce dossier reçoit les exports d'interfaces générées avec Google Stitch
(HTML/CSS, images, etc.) avant leur adaptation au design system SmartLink
(Blade + Tailwind).

**Avant de générer un écran, lisez [`PROMPT.md`](PROMPT.md)** : c'est la
consigne à coller dans Stitch, avec les valeurs exactes de la charte. Sans
elle, l'outil rend son style générique et la reprise en Blade coûte une passe
de correction entière.

Les 29 dossiers présents couvrent l'ensemble du produit et sont tous repris
dans `resources/views`. Vérifiez qu'un écran n'existe pas déjà avant d'en
demander un nouveau.

Une fois les fichiers déposés ici, ils sont repris dans les vues du projet en
respectant la charte existante. **Les maquettes font foi pour la mise en page,
jamais pour le contenu**, qui vient du modèle de données — et jamais pour le
modèle économique : plusieurs d'entre elles ont proposé des paiements de
prestation qui n'existent pas.
