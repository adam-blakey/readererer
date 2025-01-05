<?php

namespace App\Policies;

use App\Models\Composer;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\UserRole;

class ComposerPolicy
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
    public function view(User $user, Composer $composer)
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
    public function update(User $user, Composer $composer)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Composer $composer)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Composer $composer)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Composer $composer)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }
}
