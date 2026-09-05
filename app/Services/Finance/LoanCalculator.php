<?php

namespace App\Services\Finance;

use App\Enums\LoanType;
use App\Exceptions\Finance\InsufficientLoanBalanceException;
use App\Exceptions\Finance\InsufficientLoanHoldingBalanceException;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Services\Finance\Data\LoanProgress;
use App\Services\Finance\Data\LoanSummary;

/**
 * The single source of truth for a loan's outstanding balance and, for loans
 * given, its held-but-untransferred balance (spec §45). Never cached on the
 * model - always the live sum of its repayments/transfers.
 */
final class LoanCalculator
{
    public function totalRepaid(Loan $loan): Money
    {
        return Money::of($loan->repayments()->sum('amount'));
    }

    /**
     * The amount still owed - one formula for both directions, since a
     * repayment (received from a borrower, or paid to a lender) reduces it
     * identically either way.
     */
    public function outstanding(Loan $loan): Money
    {
        return Money::of($loan->amount)->subtract($this->totalRepaid($loan));
    }

    public function totalTransferred(Loan $loan): Money
    {
        return Money::of($loan->transfers()->sum('amount'));
    }

    /**
     * The portion of received repayments not yet transferred to an account.
     * Only meaningful for loans given - a loan taken never has transfers.
     */
    public function heldBalance(Loan $loan): Money
    {
        return $this->totalRepaid($loan)->subtract($this->totalTransferred($loan));
    }

    public function progress(Loan $loan): LoanProgress
    {
        return new LoanProgress(
            loan: $loan,
            totalRepaid: $this->totalRepaid($loan),
            outstanding: $this->outstanding($loan),
            totalTransferred: $this->totalTransferred($loan),
            heldBalance: $loan->type === LoanType::Given ? $this->heldBalance($loan) : Money::zero(),
        );
    }

    /**
     * Guard against a loan's outstanding balance ever going negative -
     * whether from an over-large repayment or from deleting/shrinking an
     * earlier repayment (spec §44/§58).
     */
    public function assertOutstandingNotNegative(Loan $loan): void
    {
        if ($this->outstanding($loan)->isNegative()) {
            throw new InsufficientLoanBalanceException;
        }
    }

    /**
     * Guard against a loan's held-but-untransferred balance ever going
     * negative - whether from an over-large transfer or from deleting an
     * earlier repayment a later transfer depended on.
     */
    public function assertHeldBalanceNotNegative(Loan $loan): void
    {
        if ($this->heldBalance($loan)->isNegative()) {
            throw new InsufficientLoanHoldingBalanceException;
        }
    }

    /**
     * Dashboard aggregate totals - given and taken are never mixed
     * (spec §27), so every field is direction-specific.
     */
    public function dashboardSummary(User $user): LoanSummary
    {
        $totalGiven = Money::of($user->loans()->where('type', LoanType::Given)->sum('amount'));
        $totalTaken = Money::of($user->loans()->where('type', LoanType::Taken)->sum('amount'));

        $returnedByBorrowers = Money::of(
            LoanRepayment::query()
                ->where('user_id', $user->id)
                ->whereRelation('loan', 'type', LoanType::Given->value)
                ->sum('amount'),
        );

        $paidToLenders = Money::of(
            LoanRepayment::query()
                ->where('user_id', $user->id)
                ->whereRelation('loan', 'type', LoanType::Taken->value)
                ->sum('amount'),
        );

        return new LoanSummary(
            totalGiven: $totalGiven,
            totalReturnedByBorrowers: $returnedByBorrowers,
            outstandingReceivable: $totalGiven->subtract($returnedByBorrowers),
            totalTaken: $totalTaken,
            totalPaidToLenders: $paidToLenders,
            outstandingPayable: $totalTaken->subtract($paidToLenders),
        );
    }
}
