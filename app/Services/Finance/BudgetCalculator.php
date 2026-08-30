<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\MonthlyBudget;
use App\Services\Finance\Data\BudgetSummary;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

final class BudgetCalculator
{
    public function summarize(MonthlyBudget $monthlyBudget, ?CarbonImmutable $today = null): BudgetSummary
    {
        $today ??= CarbonImmutable::now();

        $periodStart = CarbonImmutable::create($monthlyBudget->year, $monthlyBudget->month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        $budgetAmount = Money::of($monthlyBudget->amount);

        $usedAmount = Money::of((string) Expense::query()
            ->where('user_id', $monthlyBudget->user_id)
            ->where('budget_category_id', $monthlyBudget->budget_category_id)
            ->whereBetween('spent_on', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount'));

        $availableAmount = BudgetMath::available($budgetAmount, $usedAmount);
        $remainingDays = $this->remainingDaysInPeriod($monthlyBudget->year, $monthlyBudget->month, $today);

        return new BudgetSummary(
            monthlyBudget: $monthlyBudget,
            budgetAmount: $budgetAmount,
            usedAmount: $usedAmount,
            availableAmount: $availableAmount,
            isExceeded: BudgetMath::isExceeded($budgetAmount, $usedAmount),
            overBudgetAmount: BudgetMath::overBudgetAmount($budgetAmount, $usedAmount),
            remainingDays: $remainingDays,
            dailySafeSpend: BudgetMath::dailySafeSpend($availableAmount, $remainingDays),
            usagePercentage: BudgetMath::usagePercentage($budgetAmount, $usedAmount),
        );
    }

    /**
     * Remaining days in the given period, relative to $today:
     * - current month: days left including today
     * - a future month: the full number of days in that month
     * - a past month: zero
     */
    public function remainingDaysInPeriod(int $year, int $month, ?CarbonImmutable $today = null): int
    {
        return $this->calculateRemainingDays($year, $month, $today ?? CarbonImmutable::now());
    }

    private function calculateRemainingDays(int $year, int $month, CarbonImmutable|Carbon $today): int
    {
        if ($year === $today->year && $month === $today->month) {
            $daysInMonth = (int) CarbonImmutable::create($year, $month, 1)->daysInMonth;

            return max(0, $daysInMonth - (int) $today->day + 1);
        }

        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return $today->lt($periodStart) ? (int) $periodStart->daysInMonth : 0;
    }
}
