<?php

namespace App\Enums;

enum UserRole: string
{
    case RecreationAdmin = 'recreation_admin';
    case Cashier = 'cashier';
}
