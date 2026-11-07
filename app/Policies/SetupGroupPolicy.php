<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SetupGroupPolicy
{
    public function before(User $user): ?bool
    {
        return config('app.readererer_setup_groups') ? null : false;
    }

    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, SetupGroup $setupGroup)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
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

    public function update(User $user, SetupGroup $setupGroup)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, SetupGroup $setupGroup)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, SetupGroup $setupGroup)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, SetupGroup $setupGroup)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
