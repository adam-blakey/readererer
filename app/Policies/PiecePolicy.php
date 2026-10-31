<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PiecePolicy
{
    public function viewAny(User $user)
    {
        return Response::deny();
    }

    public function view(User $user, Piece $piece)
    {
        return Response::deny();
    }

    public function create(User $user)
    {
        if ($user->role->value >= UserRole::Admin->value) {
            return Response::allow();
        }

        return Response::deny();
    }

    public function update(User $user, Piece $piece)
    {
        return Response::deny();
    }

    public function delete(User $user, Piece $piece)
    {
        return Response::deny();
    }

    public function restore(User $user, Piece $piece)
    {
        return Response::deny();
    }

    public function forceDelete(User $user, Piece $piece)
    {
        return Response::deny();
    }
}
