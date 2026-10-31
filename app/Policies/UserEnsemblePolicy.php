<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserEnsemble;
use Illuminate\Auth\Access\Response;

class UserEnsemblePolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function create(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, UserEnsemble $userEnsemble)
    {
        if ($user->role >= UserRole::Admin) {
            return Response::allow();
        }

        return Response::deny();
    }
}
