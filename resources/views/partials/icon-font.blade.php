{{-- Police d'icônes, restreinte aux ligatures réellement utilisées : la police
     complète pèse 1,1 Mo, ce sous-ensemble une vingtaine de kilo-octets. Sur
     data mobile, l'écart se voit.

     « display=block » plutôt que « swap » : sans lui, le navigateur affiche le
     nom de la ligature — « location_on », « arrow_forward » — tant que la
     police n'est pas arrivée.

     La liste est partagée par les deux gabarits pour qu'elles ne divergent pas.
     Après avoir ajouté une icône dans une vue, régénérer avec :
     php artisan icons:sync --}}
<link
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&icon_names={{ implode(',', config('icons.names')) }}&display=block"
    rel="stylesheet"
>
