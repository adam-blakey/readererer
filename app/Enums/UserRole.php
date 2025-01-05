<?php

namespace App\Enums;

enum UserRole: int
{
    case Guest = 0; // No access to anything.
    case Member = 1; // Standard access; view most things, edit and update very little.
    case Moderator = 2; // View and edit of most things; can't change important config settings.
    case Admin = 3; // Full access to everything.
}