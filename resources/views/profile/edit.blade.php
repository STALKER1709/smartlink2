<x-app-layout titre="Paramètres du compte" :indexable="false">
    <x-slot name="header">
        <x-page-header :title="__('Profile')"
                       :subtitle="__('Manage your personal information and account settings.')" />
    </x-slot>

    {{--
        Les maquettes posent un bouton « Enregistrer » fixe en bas d'écran. Il
        n'est pas repris : la page porte trois formulaires indépendants, et un
        bouton unique en aurait soumis un seul. Un visiteur qui vient de saisir
        son nouveau mot de passe et qui appuie dessus enregistrerait ses
        coordonnées et perdrait sa saisie, sans rien qui le lui dise. Chaque
        carte garde donc son propre bouton, au pied de son formulaire.
    --}}
    <div class="mx-auto max-w-container space-y-8 px-margin-mobile py-8 md:px-margin-desktop">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
        @include('profile.partials.delete-user-form')
    </div>
</x-app-layout>
