<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Composer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComposerPolicy
{
    public function viewAny(User $user)
    {
        return Response::deny();
    }

    public function view(User $user, Composer $composer)
    {
        return $user->role->value >= UserRole::Moderator->value ? Response::allow() : Response::deny();
    }

    public function create(User $user)
    {
        return Response::deny();
    }

    public function update(User $user, Composer $composer)
    {
        return $user->role->value >= UserRole::Admin->value ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Composer $composer)
    {
        return Response::deny();
    }

    public function restore(User $user, Composer $composer)
    {
        return Response::deny();
    }

    public function forceDelete(User $user, Composer $composer)
    {
        return Response::deny();
    }
}
