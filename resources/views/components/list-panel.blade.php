{{--
    Le conteneur d'une liste : administration, tableau de bord, écrans du prestataire.

    Une seule boîte autour de la liste entière, jamais une boîte par ligne :
    quinze cartes identiques posées les unes sous les autres ne hiérarchisent
    rien. À fond perdu sur mobile, où l'écran est trop étroit pour offrir une
    marge à un cadre.

    La bordure basse appartient à la ligne (`x-list-row`) et non au
    conteneur : `divide-y` la remet à zéro sur tous les enfants sauf le
    premier, ce qui ne laisse qu'un filet dès qu'on passe en deux colonnes.
    Le conteneur ne porte donc que le filet du haut, la dernière ligne
    fermant la liste par le sien.
--}}
<div {{ $attributes->merge(['class' => '-mx-margin-mobile border-t border-outline-variant bg-surface-container-lowest px-margin-mobile md:mx-0 md:rounded-xl md:border md:border-b-0 md:px-6']) }}>
    {{ $slot }}
</div>
