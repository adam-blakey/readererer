<?php

namespace App\Enums;

enum UserRole: int
{
    case Guest = 0; // No access to anything.
    case Ensemble = 1; // Generic ensemble login; only able to update attendance registers.
    case Member = 2; // Standard access; view most things, edit and update very little.
    case Moderator = 3; // View and edit of most things; can't change important config settings.
    case Admin = 4; // Full access to everything.
}
