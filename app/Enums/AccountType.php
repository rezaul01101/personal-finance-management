<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case MobileWallet = 'mobile_wallet';
    case Card = 'card';
    case Other = 'other';
}
