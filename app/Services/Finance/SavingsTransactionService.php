<?php

namespace App\Services\Finance;

use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete a SavingsTransaction.
 * A contribution grows the goal; a withdrawal shrinks it. The goal's
 * balance is never allowed to go negative (spec Rule 2/23/44/46), including
 * retroactively via editing or deleting an earlier contribution.
 */
final class SavingsTransactionService
{
    public function __construct(
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

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(SavingsTransaction $transaction, array $attributes): SavingsTransaction
    {
        return DB::transaction(function () use ($transaction, $attributes) {
            $transaction->update($attributes);

            $this->savingsCalculator->assertNotNegative($transaction->savingsGoal);

            return $transaction;
        });
    }

    public function delete(SavingsTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $goal = $transaction->savingsGoal;

            $transaction->delete();

            $this->savingsCalculator->assertNotNegative($goal);
        });
    }
}
