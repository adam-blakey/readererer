<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Features\GuestPolls;
use App\Models\Ensemble;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Laravel\Pennant\Feature;

class EnsemblePolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, Ensemble $ensemble)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }
        if ($user->role->value >= UserRole::Ensemble->value && $user->ensembles->contains($ensemble)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function viewPoll(User $user, Ensemble $ensemble)
    {
        if ($user->role === UserRole::Guest
            && Feature::for($user)->active(GuestPolls::class)
            && $user->ensembles->firstWhere('id', $ensemble->id)?->pivot->instrument_family_id !== null) {
            return Response::allow();
        }

        return $this->view($user, $ensemble);
    }

    public function create(User $user)
    {
        if (config('app.readererer_single_ensemble') && Ensemble::exists()) {
            return Response::deny(__('This site is set up for a single ensemble.'));
        }

        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, Ensemble $ensemble)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, Ensemble $ensemble)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, Ensemble $ensemble)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, Ensemble $ensemble)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
