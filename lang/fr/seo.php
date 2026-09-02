<?php

return [
    /*
     * Le titre de la page d'accueil et le repli de toutes les pages qui n'en
     * fournissent pas. Il porte la promesse et la ville : c'est ce que lit
     * quelqu'un qui cherche « plombier Douala » sur un moteur.
     */
    'default_title' => 'SmartLink — Trouvez un prestataire de confiance au Cameroun',

    'default_description' => 'Plombiers, électriciens, coiffeurs, ménage, cours particuliers : trouvez un prestataire vérifié près de chez vous au Cameroun, échangez directement et convenez du prix ensemble.',

    'services_index' => 'Tous les services',
    'services_index_description' => 'Parcourez les services proposés par les prestataires vérifiés de SmartLink : plomberie, électricité, ménage, coiffure, cours particuliers et bien d\'autres, partout au Cameroun.',

    'providers_index' => 'Tous les prestataires',
    'providers_index_description' => 'Découvrez les prestataires de services vérifiés du Cameroun : consultez leurs avis, leurs tarifs indicatifs et leur zone d\'intervention avant de les contacter.',

    'help' => 'Centre d\'aide',
    'help_description' => 'Comment créer un compte, envoyer une demande, laisser un avis : les réponses aux questions les plus fréquentes sur SmartLink.',

    'login' => 'Se connecter',
    'register' => 'Créer un compte',
    'register_description' => 'Créez votre compte SmartLink en une minute : client pour trouver un prestataire, prestataire pour recevoir des demandes. 30 jours d\'essai gratuit pour les prestataires.',

    /*
     * Fiches. `:ville` reste au singulier même quand le prestataire couvre
     * plusieurs quartiers : c'est la ville que les gens tapent.
     */
    'service' => ':titre à :ville',
    'service_no_city' => ':titre',
    'provider' => ':nom — :metier à :ville',
    'provider_no_city' => ':nom — :metier',
];
