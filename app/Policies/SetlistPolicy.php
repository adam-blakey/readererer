<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Setlist;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SetlistPolicy
{
    public function viewAny(User $user)
    {
        return Response::deny();
    }

    public function view(User $user, Setlist $setlist)
    {
        return Response::deny();
    }

    public function create(User $user)
    {
        return Response::deny();
    }

    public function update(User $user, Setlist $setlist)
    {
        return Response::deny();
    }

    public function delete(User $user, Setlist $setlist)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, Setlist $setlist)
    {
        return Response::deny();
    }

    public function forceDelete(User $user, Setlist $setlist)
    {
        return Response::deny();
    }
}
