<?php

namespace App\Services\Finance;

use App\Models\Account;

/**
 * The sole mutator of `accounts.balance` (spec §45/§19). This service has no
 * concept of income/expense/loan/etc - callers decide whether to credit or
 * debit, which is what keeps those business rules at the call site rather
 * than baked into this primitive.
 *
 * Must always be called from within a DB::transaction() opened by the
 * caller, so the row lock taken here actually holds for the duration of the
 * surrounding financial operation.
 */
final class AccountBalanceService
{
    public function credit(Account $account, Money $amount): void
    {
        $this->applyDelta($account, $amount);
    }

    public function debit(Account $account, Money $amount): void
    {
        $this->applyDelta($account, $amount->multiply('-1'));
    }

    private function applyDelta(Account $account, Money $delta): void
    {
        /** @var Account $locked */
        $locked = Account::query()->whereKey($account->getKey())->lockForUpdate()->firstOrFail();

        $newBalance = Money::of($locked->balance)->add($delta);

        $locked->forceFill(['balance' => $newBalance->toDecimalString()])->save();

        $account->balance = $newBalance->toDecimalString();
    }
}
