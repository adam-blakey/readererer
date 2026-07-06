<?php

namespace App\Policies;

use App\Models\EnsembleAdmin;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\UserRole;

class EnsembleAdminPolicy
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
    public function view(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        // Members may only view the admins of an ensemble they belong to.
        if ($user->role->value >= UserRole::Member->value && $user->ensembles->contains('id', $ensembleAdmin->ensemble_id)) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
