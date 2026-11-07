<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Moderator->value || $this->isOwnMemberAccount($user, $user_)) {
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

    public function update(User $user, User $user_)
    {
        if ($this->isOwnMemberAccount($user, $user_)) {
            return Response::allow();
        }

        return $this->manage($user, $user_);
    }

    public function manage(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Moderator->value && $user_->role->value <= $user->role->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Moderator->value && $user_->role->value <= $user->role->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function erase(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Admin->value && $user->isNot($user_)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function resetPassword(User $user, User $user_)
    {
        if ($user->role->value >= UserRole::Admin->value && $user->isNot($user_)) {
            return Response::allow();
        }

        return Response::deny();
    }

    private function isOwnMemberAccount(User $user, User $user_): bool
    {
        return $user->is($user_) && $user->role->value >= UserRole::Member->value;
    }
}
