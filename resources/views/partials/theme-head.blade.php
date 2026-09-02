{{--
    Le schéma de couleurs, posé avant le premier pixel.

    Ce script est en ligne et synchrone, et les deux le sont pour la même
    raison : il doit s'exécuter avant que le navigateur ne peigne quoi que ce
    soit. Différé, ou chargé depuis un fichier, la page s'affiche d'abord dans
    le schéma par défaut puis bascule — l'éclair blanc que tout le monde
    connaît pour l'avoir subi, et qui est pire en sombre qu'en clair.

    Trois états, pas deux. `data-theme` absent suit le réglage du système ;
    « light » et « dark » sont des choix explicites qui l'emportent dans les
    deux sens. Sans ce troisième état, un visiteur qui a choisi le clair sur
    un système réglé en sombre reverrait le sombre à chaque visite.

    Le `try` n'est pas décoratif : `localStorage` lève quand les cookies tiers
    sont bloqués, en navigation privée sur certains navigateurs, et dans une
    iframe sans permission. Sans lui, l'exception arrête le script et la page
    reste au schéma par défaut.
--}}
<script>
    (function () {
        try {
            var choix = localStorage.getItem('smartlink-theme');
            if (choix === 'dark' || choix === 'light') {
                document.documentElement.setAttribute('data-theme', choix);
            }
        } catch (e) {}
    })();
</script>
