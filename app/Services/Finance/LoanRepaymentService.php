<?php

namespace App\Services\Finance;

use App\Enums\LoanType;
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
            }

            $repayment->update($attributes);

            $this->loanCalculator->assertOutstandingNotNegative($loan);

            if ($loan->type === LoanType::Given) {
                $this->loanCalculator->assertHeldBalanceNotNegative($loan);
            }

            return $repayment;
        });
    }

    public function delete(LoanRepayment $repayment): void
    {
        DB::transaction(function () use ($repayment) {
            $loan = $repayment->loan;
            $type = $loan->type;

            $repayment->delete();

            if ($type === LoanType::Given) {
                $this->loanCalculator->assertHeldBalanceNotNegative($loan);
            }
        });
    }
}
