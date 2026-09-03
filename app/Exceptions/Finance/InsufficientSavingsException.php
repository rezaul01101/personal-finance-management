<?php

namespace App\Exceptions\Finance;

use RuntimeException;

/**
 * Thrown when a savings withdrawal (or the deletion of a contribution) would
 * push a savings goal's balance below zero (spec §23/§44 - withdrawals can
 * never exceed the currently saved amount).
 */
class InsufficientSavingsException extends RuntimeException
{
    public function __construct(string $message = 'This would take the savings goal below zero.')
    {
        parent::__construct($message);
    }
}
