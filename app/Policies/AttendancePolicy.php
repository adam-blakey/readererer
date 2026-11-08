<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendancePolicy
{
    public function viewAny(User $user)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function view(User $user, Attendance $attendance)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function create(User $user, ?Ensemble $ensemble = null)
    {
        if ($user->role->value >= UserRole::Moderator->value) {
            return Response::allow();
        }

        if ($ensemble && $user->role->value >= UserRole::Ensemble->value && $user->ensembles->contains($ensemble)) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function delete(User $user, Attendance $attendance)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function restore(User $user, Attendance $attendance)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function forceDelete(User $user, Attendance $attendance)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }
}
