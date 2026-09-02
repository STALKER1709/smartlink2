<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin()
            || $user->id === $serviceRequest->client_id
            || $user->id === $serviceRequest->provider_id;
    }

    /**
     * Ce refus-là se voit, alors il s'explique.
     *
     * Le bouton « Faire une demande » est offert au visiteur non connecté :
     * on ignore encore qui il est. S'il se connecte avec un compte
     * prestataire, `redirect()->intended()` le ramène ici, et la Policy
     * refuse — à juste titre, mais sur « This action is unauthorized. », en
     * anglais, sur la page 403 nue de Laravel.
     *
     * `Response::deny()` porte le message jusqu'à `errors/403`, qui l'affiche
     * à la place du texte générique. Un `false` n'aurait rien à dire.
     */
    public function create(User $user): Response
    {
        if (! $user->isClient()) {
            return Response::deny(__("Cette page est réservée aux clients. Votre compte publie des services, il n'en demande pas."));
        }

        if (! $user->isActive()) {
            return Response::deny(__('Votre compte est suspendu : vous ne pouvez pas envoyer de demande.'));
        }

        return Response::allow();
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->client_id || $user->id === $serviceRequest->provider_id;
    }

    /**
     * Provider-only actions: accept, refuse, start, complete.
     */
    public function respond(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->id === $serviceRequest->provider_id && $user->isActive();
    }

    /**
     * Either participant may cancel a request that is not yet final.
     */
    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        return ! $serviceRequest->isFinal()
            && ($user->id === $serviceRequest->client_id || $user->id === $serviceRequest->provider_id);
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->isAdmin();
    }
}
