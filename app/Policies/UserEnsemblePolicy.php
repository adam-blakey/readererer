<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserEnsemble;
use Illuminate\Auth\Access\Response;
use App\Enums\UserRole;

class UserEnsemblePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }
}
