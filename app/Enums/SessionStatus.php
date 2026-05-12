<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
