<?php

namespace App\Enums;

enum AttendanceStatus: int
{
    case Unknown = 0;
    case Attending = 1;
    case NotAttending = 2;
}