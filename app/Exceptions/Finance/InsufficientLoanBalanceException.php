<?php

namespace App\Exceptions\Finance;

use RuntimeException;

/**
 * Thrown when a repayment (or the deletion of one) would push a loan's
 * outstanding balance below zero (spec §44/§58 - a repayment can never
 * exceed the currently outstanding amount).
 */
class InsufficientLoanBalanceException extends RuntimeException
{
    public function __construct(string $message = "This would take the loan's outstanding balance below zero.")
    {
        parent::__construct($message);
    }
}
