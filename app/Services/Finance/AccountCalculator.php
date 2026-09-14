<?php

namespace App\Services\Finance;

use App\Enums\LoanType;
use App\Enums\SavingsTransactionType;
use App\Models\Account;
use App\Services\Finance\Data\AccountSummary;

/**
 * The single source of truth for an account's current balance. `balance` on
 * the model itself is the fixed opening balance set at creation and never
 * mutated afterwards - the current balance is always the live sum of every
 * transaction recorded against the account on top of that opening figure.
 */
final class AccountCalculator
{
    public function openingBalance(Account $account): Money
    {
        return Money::of($account->balance);
    }

    /**
     * Everything that increases the account: income received, transfers in,
     * loans taken (money borrowed in), a loan-given's held balance being
     * transferred in, and savings withdrawals.
     */
    public function totalCredits(Account $account): Money
    {
        return Money::of($account->incomes()->sum('amount'))
            ->add(Money::of($account->incomingTransfers()->sum('amount')))
            ->add(Money::of($account->loans()->where('type', LoanType::Taken)->sum('amount')))
            ->add(Money::of($account->loanTransfers()->sum('amount')))
            ->add(Money::of($account->savingsTransactions()->where('type', SavingsTransactionType::Withdrawal)->sum('amount')));
    }

    /**
     * Everything that decreases the account: expenses, transfers out, loans
     * given (money lent out), repayments on a loan taken, and savings
     * contributions.
     */
    public function totalDebits(Account $account): Money
    {
        return Money::of($account->expenses()->sum('amount'))
            ->add(Money::of($account->outgoingTransfers()->sum('amount')))
            ->add(Money::of($account->loans()->where('type', LoanType::Given)->sum('amount')))
            ->add(Money::of($account->loanRepayments()->sum('amount')))
            ->add(Money::of($account->savingsTransactions()->where('type', SavingsTransactionType::Contribution)->sum('amount')));
    }

    public function currentBalance(Account $account): Money
    {
        return $this->openingBalance($account)
            ->add($this->totalCredits($account))
            ->subtract($this->totalDebits($account));
    }

    public function summarize(Account $account): AccountSummary
    {
        $opening = $this->openingBalance($account);
        $credits = $this->totalCredits($account);
        $debits = $this->totalDebits($account);

        return new AccountSummary(
            account: $account,
            openingBalance: $opening,
            totalCredits: $credits,
            totalDebits: $debits,
            currentBalance: $opening->add($credits)->subtract($debits),
        );
    }
}
