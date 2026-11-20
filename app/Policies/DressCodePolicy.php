<?php

namespace App\Policies;

use App\Models\DressCode;
use App\Models\User;

class DressCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view.settings');
    }

    public function view(User $user, DressCode $dressCode): bool
    {
        return $user->can('view.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('edit.settings');
    }

    public function update(User $user, DressCode $dressCode): bool
    {
        return $user->can('edit.settings');
    }

    public function delete(User $user, DressCode $dressCode): bool
    {
        return $user->can('edit.settings');
    }

    public function restore(User $user, DressCode $dressCode): bool
    {
        return $user->can('edit.settings');
    }

    public function forceDelete(User $user, DressCode $dressCode): bool
    {
        return $user->can('edit.settings');
    }
}
