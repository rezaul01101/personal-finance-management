<?php

namespace App\Services\Finance\Data;

use App\Models\SavingsGoal;
use App\Services\Finance\Money;

final readonly class SavingsSummary
{
    public function __construct(
        public SavingsGoal $savingsGoal,
        public Money $savedAmount,
        public Money $targetAmount,
        public Money $remainingAmount,
        public float $usagePercentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'savings_goal_id' => $this->savingsGoal->id,
            'saved_amount' => $this->savedAmount->toDecimalString(),
            'target_amount' => $this->targetAmount->toDecimalString(),
            'remaining_amount' => $this->remainingAmount->toDecimalString(),
            'usage_percentage' => $this->usagePercentage,
        ];
    }
}
