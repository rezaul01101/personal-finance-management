<?php

namespace App\Services\Finance\Data;

use App\Models\MonthlyBudget;
use App\Services\Finance\Money;

final readonly class BudgetSummary
{
    public function __construct(
        public MonthlyBudget $monthlyBudget,
        public Money $budgetAmount,
        public Money $usedAmount,
        public Money $availableAmount,
        public bool $isExceeded,
        public Money $overBudgetAmount,
        public int $remainingDays,
        public Money $dailySafeSpend,
        public float $usagePercentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'budget_category_id' => $this->monthlyBudget->budget_category_id,
            'budget_amount' => $this->budgetAmount->toDecimalString(),
            'used_amount' => $this->usedAmount->toDecimalString(),
            'available_amount' => $this->availableAmount->toDecimalString(),
            'is_exceeded' => $this->isExceeded,
            'over_budget_amount' => $this->overBudgetAmount->toDecimalString(),
            'remaining_days' => $this->remainingDays,
            'daily_safe_spend' => $this->dailySafeSpend->toDecimalString(),
            'usage_percentage' => $this->usagePercentage,
        ];
    }
}
