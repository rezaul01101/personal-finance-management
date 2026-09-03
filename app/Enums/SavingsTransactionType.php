<?php

namespace App\Enums;

enum SavingsTransactionType: string
{
    case Contribution = 'contribution';
    case Withdrawal = 'withdrawal';
}
