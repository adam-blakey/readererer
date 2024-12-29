<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Unknown = 0;
    case NotAttending = 1;
    case Attending = 2;
}
