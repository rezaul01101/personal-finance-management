<?php

namespace App\Services\Finance;

use App\Enums\SavingsTransactionType;
use App\Exceptions\Finance\InsufficientSavingsException;
use App\Models\SavingsGoal;
use App\Services\Finance\Data\SavingsSummary;

/**
 * The single source of truth for a savings goal's saved amount (spec §45).
 * Never cached on the model - always the live sum of its transactions.
 */
final class SavingsCalculator
{
    public function balance(SavingsGoal $goal): Money
    {
        $contributed = Money::of($goal->transactions()
            ->where('type', SavingsTransactionType::Contribution)
            ->sum('amount'));

        $withdrawn = Money::of($goal->transactions()
            ->where('type', SavingsTransactionType::Withdrawal)
            ->sum('amount'));

        return $contributed->subtract($withdrawn);
    }

    public function summarize(SavingsGoal $goal): SavingsSummary
    {
        $saved = $this->balance($goal);
        $target = Money::of($goal->target_amount);

        return new SavingsSummary(
            savingsGoal: $goal,
            savedAmount: $saved,
            targetAmount: $target,
            remainingAmount: $target->subtract($saved),
            usagePercentage: BudgetMath::usagePercentage($target, $saved),
        );
    }

    /**
     * Guard against a savings goal's balance ever going negative - whether
     * from an over-large withdrawal or from deleting/shrinking an earlier
     * contribution that a later withdrawal depended on (spec §23/§44).
     */
    public function assertNotNegative(SavingsGoal $goal): void
    {
        if ($this->balance($goal)->isNegative()) {
            throw new InsufficientSavingsException;
        }
    }
}
