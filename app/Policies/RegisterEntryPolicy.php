<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RegisterEntry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RegisterEntryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RegisterEntry $registerEntry)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RegisterEntry $registerEntry)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RegisterEntry $registerEntry)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RegisterEntry $registerEntry)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RegisterEntry $registerEntry)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
