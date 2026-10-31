<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EnsemblePolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, Ensemble $ensemble)
    {
        // Admins always allowed; otherwise allow users tied to this ensemble (including Ensemble role users).
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }
        if ($user->role->value >= UserRole::Ensemble->value && $user->ensembles->contains($ensemble)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function create(User $user)
    {
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
