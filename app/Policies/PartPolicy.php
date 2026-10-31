<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Part;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PartPolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value->value >= UserRole->value::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, Part $parts)
    {
        if ($user->role->value >= UserRole::Admin->value) {
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

    public function update(User $user, Part $parts)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, Part $parts)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, Part $parts)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, Part $parts)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
