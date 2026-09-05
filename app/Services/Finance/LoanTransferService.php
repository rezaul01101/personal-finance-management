<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete a LoanTransfer - moves
 * money out of a loan given's held-but-untransferred pool (built up by
 * repayments) into a real account. Only meaningful for loans given; the
 * controller is responsible for rejecting this for loans taken.
 */
final class LoanTransferService
{
    public function __construct(
        private readonly AccountBalanceService $accountBalance,
        private readonly LoanCalculator $loanCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, Loan $loan, array $attributes): LoanTransfer
    {
        return DB::transaction(function () use ($user, $loan, $attributes) {
            $transfer = $user->loanTransfers()->create([
                ...$attributes,
                'loan_id' => $loan->id,
            ]);

            $this->loanCalculator->assertHeldBalanceNotNegative($loan);

            $this->accountBalance->credit($transfer->account, Money::of($transfer->amount));

            return $transfer;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(LoanTransfer $transfer, array $attributes): LoanTransfer
    {
        return DB::transaction(function () use ($transfer, $attributes) {
            $loan = $transfer->loan;
            $oldAccount = $transfer->account;
            $oldAmount = Money::of($transfer->amount);

            $this->accountBalance->debit($oldAccount, $oldAmount);

            $transfer->update($attributes);

            $this->loanCalculator->assertHeldBalanceNotNegative($loan);

            $newAccount = $transfer->account_id === $oldAccount->id
                ? $oldAccount
                : Account::query()->findOrFail($transfer->account_id);

            $this->accountBalance->credit($newAccount, Money::of($transfer->amount));

            return $transfer;
        });
    }

    public function delete(LoanTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $this->accountBalance->debit($transfer->account, Money::of($transfer->amount));

            $transfer->delete();
        });
    }
}
