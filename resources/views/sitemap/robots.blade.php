User-agent: *

# Les espaces privés n'ont rien à faire dans un index : ils redirigent vers
# la connexion, ce qui gaspille le budget d'exploration et expose la
# structure des URL sans rien apporter.
Disallow: /admin
Disallow: /dashboard
Disallow: /provider/
Disallow: /client/
Disallow: /requests
Disallow: /conversations
Disallow: /notifications
Disallow: /favoris
Disallow: /litiges
Disallow: /profile
Disallow: /phone/
Disallow: /cron/

# La page d'attente hors connexion : servie par le service worker, elle n'a
# aucun contenu propre.
Disallow: /hors-ligne

# Ce qui fait venir : les fiches de services et de prestataires, l'accueil,
# l'aide et les pages légales restent ouvertes.

Sitemap: {{ route('sitemap') }}
