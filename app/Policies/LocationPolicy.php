<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view.settings');
    }

    public function view(User $user, Location $location): bool
    {
        return $user->can('view.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('edit.settings');
    }

    public function update(User $user, Location $location): bool
    {
        return $user->can('edit.settings');
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->can('edit.settings');
    }

    public function restore(User $user, Location $location): bool
    {
        return $user->can('edit.settings');
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return $user->can('edit.settings');
    }
}
