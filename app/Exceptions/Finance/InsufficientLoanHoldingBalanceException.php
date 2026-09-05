<?php

namespace App\Exceptions\Finance;

use RuntimeException;

/**
 * Thrown when a transfer (or the deletion of a repayment it depends on)
 * would push a loan's held-but-untransferred balance below zero. Repayments
 * received on a loan given are held until explicitly transferred to an
 * account; a transfer can never exceed what is still held.
 */
class InsufficientLoanHoldingBalanceException extends RuntimeException
{
    public function __construct(string $message = "This would take the loan's held (untransferred) balance below zero.")
    {
        parent::__construct($message);
    }
}
