<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EnsembleAdmin;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EnsembleAdminPolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

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

    public function create(User $user)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, EnsembleAdmin $ensembleAdmin)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
