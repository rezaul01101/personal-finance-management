<?php

namespace App\Services\Finance\Data;

use App\Models\Loan;
use App\Services\Finance\Money;

final readonly class LoanProgress
{
    public function __construct(
        public Loan $loan,
        public Money $totalRepaid,
        public Money $outstanding,
        public Money $totalTransferred,
        public Money $heldBalance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'loan_id' => $this->loan->id,
            'total_repaid' => $this->totalRepaid->toDecimalString(),
            'outstanding' => $this->outstanding->toDecimalString(),
            'total_transferred' => $this->totalTransferred->toDecimalString(),
            'held_balance' => $this->heldBalance->toDecimalString(),
        ];
    }
}
