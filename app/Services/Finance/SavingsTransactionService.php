<?php

namespace App\Services\Finance;

use App\Enums\SavingsTransactionType;
use App\Models\Account;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete a SavingsTransaction.
 * A contribution debits the account and grows the goal; a withdrawal
 * credits the account and shrinks the goal. The goal's balance is never
 * allowed to go negative (spec Rule 2/23/44/46), including retroactively
 * via editing or deleting an earlier contribution.
 */
final class SavingsTransactionService
{
    public function __construct(
        private readonly AccountBalanceService $accountBalance,
        private readonly SavingsCalculator $savingsCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): SavingsTransaction
    {
        return DB::transaction(function () use ($user, $attributes) {
            $transaction = $user->savingsTransactions()->create($attributes);

            $this->savingsCalculator->assertNotNegative($transaction->savingsGoal);

            $this->applyAccountEffect($transaction->account, $transaction->type, Money::of($transaction->amount));

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(SavingsTransaction $transaction, array $attributes): SavingsTransaction
    {
        return DB::transaction(function () use ($transaction, $attributes) {
            $oldAccount = $transaction->account;
            $oldType = $transaction->type;
            $oldAmount = Money::of($transaction->amount);

            $this->reverseAccountEffect($oldAccount, $oldType, $oldAmount);

            $transaction->update($attributes);

            $this->savingsCalculator->assertNotNegative($transaction->savingsGoal);

            $newAccount = $transaction->account_id === $oldAccount->id
                ? $oldAccount
                : Account::query()->findOrFail($transaction->account_id);

            $this->applyAccountEffect($newAccount, $transaction->type, Money::of($transaction->amount));

            return $transaction;
        });
    }

    public function delete(SavingsTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $goal = $transaction->savingsGoal;
            $account = $transaction->account;
            $type = $transaction->type;
            $amount = Money::of($transaction->amount);

            $transaction->delete();

            $this->savingsCalculator->assertNotNegative($goal);

            $this->reverseAccountEffect($account, $type, $amount);
        });
    }

    private function applyAccountEffect(Account $account, SavingsTransactionType $type, Money $amount): void
    {
        match ($type) {
            SavingsTransactionType::Contribution => $this->accountBalance->debit($account, $amount),
            SavingsTransactionType::Withdrawal => $this->accountBalance->credit($account, $amount),
        };
    }

    private function reverseAccountEffect(Account $account, SavingsTransactionType $type, Money $amount): void
    {
        match ($type) {
            SavingsTransactionType::Contribution => $this->accountBalance->credit($account, $amount),
            SavingsTransactionType::Withdrawal => $this->accountBalance->debit($account, $amount),
        };
    }
}
