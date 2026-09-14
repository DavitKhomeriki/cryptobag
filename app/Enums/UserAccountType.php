<?php

namespace App\Enums;

enum UserAccountType: string
{
    case User = 'user';
    case Merchant = 'merchant';
}
