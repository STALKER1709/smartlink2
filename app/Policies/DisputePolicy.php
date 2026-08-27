<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\ServiceRequest;
use App\Models\User;

class DisputePolicy
{
    /**
     * Signaler suppose d'être partie à la demande, que celle-ci soit engagée,
     * et de ne pas avoir déjà un signalement ouvert dessus.
     *
     * Un litige avant l'acceptation n'a rien à trancher : il n'y a pas encore
     * de prestation. Et un second signalement ouvert sur la même demande ne
     * ferait que dédoubler le travail de l'équipe.
     */
    public function create(User $user, ServiceRequest $request): bool
    {
        $partie = $user->id === $request->client_id || $user->id === $request->provider_id;

        $engagee = in_array($request->status, [
            ServiceRequest::STATUS_ACCEPTED,
            ServiceRequest::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_COMPLETED,
        ], true);

        $dejaOuvert = Dispute::query()
            ->where('request_id', $request->id)
            ->where('reporter_id', $user->id)
            ->open()
            ->exists();

        return $partie && $engagee && ! $dejaOuvert && $user->isActive();
    }

    public function view(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin() || $user->id === $dispute->reporter_id;
    }

    public function resolve(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin();
    }
}
