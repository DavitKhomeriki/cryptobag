<?php

namespace App\Enums;

enum TransactionType: string
{
    case Sent = 'sent';
    case Received = 'received';
}
