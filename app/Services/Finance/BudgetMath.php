<?php

namespace App\Services\Finance;

/**
 * Pure budget arithmetic (spec ".plan/Personal Finance & Budget Tracker.md" §6).
 *
 * No framework or database dependency - this is the single source of truth
 * for budget calculations and is unit-tested directly against the spec's
 * worked examples and edge cases.
 */
final class BudgetMath
{
    public static function available(Money $budget, Money $used): Money
    {
        return $budget->subtract($used);
    }

    public static function isExceeded(Money $budget, Money $used): bool
    {
        return $used->compareTo($budget) > 0;
    }

    public static function overBudgetAmount(Money $budget, Money $used): Money
    {
        $over = $used->subtract($budget);

        return $over->isNegative() ? Money::zero() : $over;
    }

    /**
     * Zero when there are no remaining days, or the budget is already
     * exceeded (available <= 0) - a negative daily spend is never shown.
     */
    public static function dailySafeSpend(Money $available, int $remainingDays): Money
    {
        if ($remainingDays <= 0 || $available->isNegative() || $available->isZero()) {
            return Money::zero();
        }

        return $available->divideOrZero($remainingDays);
    }

    public static function usagePercentage(Money $budget, Money $used): float
    {
        if ($budget->isZero()) {
            return 0.0;
        }

        return (float) bcmul(
            bcdiv($used->toDecimalString(), $budget->toDecimalString(), 4),
            '100',
            2,
        );
    }
}
