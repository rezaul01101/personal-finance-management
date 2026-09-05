<?php

namespace App\Services\Finance\Data;

use App\Services\Finance\Money;

final readonly class LoanSummary
{
    public function __construct(
        public Money $totalGiven,
        public Money $totalReturnedByBorrowers,
        public Money $outstandingReceivable,
        public Money $totalTaken,
        public Money $totalPaidToLenders,
        public Money $outstandingPayable,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_given' => $this->totalGiven->toDecimalString(),
            'total_returned_by_borrowers' => $this->totalReturnedByBorrowers->toDecimalString(),
            'outstanding_receivable' => $this->outstandingReceivable->toDecimalString(),
            'total_taken' => $this->totalTaken->toDecimalString(),
            'total_paid_to_lenders' => $this->totalPaidToLenders->toDecimalString(),
            'outstanding_payable' => $this->outstandingPayable->toDecimalString(),
        ];
    }
}
