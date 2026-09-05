<?php

namespace App\Services\Finance;

use App\Enums\LoanType;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete a LoanRepayment.
 *
 * A repayment on a loan taken (paying the lender back) immediately debits
 * the chosen account, exactly like an Expense. A repayment on a loan given
 * (the borrower returning money) never touches an account - it only reduces
 * the loan's outstanding balance and grows the held-but-untransferred pool;
 * moving that money into an account is a separate LoanTransfer.
 */
final class LoanRepaymentService
{
    public function __construct(
        private readonly AccountBalanceService $accountBalance,
        private readonly LoanCalculator $loanCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, Loan $loan, array $attributes): LoanRepayment
    {
        return DB::transaction(function () use ($user, $loan, $attributes) {
            if ($loan->type === LoanType::Given) {
                $attributes['account_id'] = null;
            }

            $repayment = $user->loanRepayments()->create([
                ...$attributes,
                'loan_id' => $loan->id,
            ]);

            $this->loanCalculator->assertOutstandingNotNegative($loan);

            if ($loan->type === LoanType::Taken) {
                $this->accountBalance->debit($repayment->account, Money::of($repayment->amount));
            }

            return $repayment;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(LoanRepayment $repayment, array $attributes): LoanRepayment
    {
        return DB::transaction(function () use ($repayment, $attributes) {
            $loan = $repayment->loan;

            if ($loan->type === LoanType::Given) {
                $attributes['account_id'] = null;
            } else {
                $oldAccount = $repayment->account;
                $oldAmount = Money::of($repayment->amount);
                $this->accountBalance->credit($oldAccount, $oldAmount);
            }

            $repayment->update($attributes);

            $this->loanCalculator->assertOutstandingNotNegative($loan);

            if ($loan->type === LoanType::Given) {
                $this->loanCalculator->assertHeldBalanceNotNegative($loan);
            } else {
                $newAccount = $repayment->account_id === $oldAccount->id
                    ? $oldAccount
                    : Account::query()->findOrFail($repayment->account_id);

                $this->accountBalance->debit($newAccount, Money::of($repayment->amount));
            }

            return $repayment;
        });
    }

    public function delete(LoanRepayment $repayment): void
    {
        DB::transaction(function () use ($repayment) {
            $loan = $repayment->loan;
            $type = $loan->type;
            $account = $repayment->account;
            $amount = Money::of($repayment->amount);

            $repayment->delete();

            if ($type === LoanType::Given) {
                $this->loanCalculator->assertHeldBalanceNotNegative($loan);
            } else {
                $this->accountBalance->credit($account, $amount);
            }
        });
    }
}
