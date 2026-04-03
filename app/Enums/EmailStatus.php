<?php

namespace App\Enums;

enum EmailStatus: int
{
    case Pending = 0;
    case Sent = 1;
    case Failed = 2;
}
