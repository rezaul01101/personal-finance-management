<?php

namespace App\Services\Finance\Data;

use App\Models\Account;
use App\Services\Finance\Money;

final readonly class AccountSummary
{
    public function __construct(
        public Account $account,
        public Money $openingBalance,
        public Money $totalCredits,
        public Money $totalDebits,
        public Money $currentBalance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->account->id,
            'opening_balance' => $this->openingBalance->toDecimalString(),
            'total_credits' => $this->totalCredits->toDecimalString(),
            'total_debits' => $this->totalDebits->toDecimalString(),
            'current_balance' => $this->currentBalance->toDecimalString(),
        ];
    }
}
