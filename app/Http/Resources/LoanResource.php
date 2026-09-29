<?php

namespace App\Http\Resources;

use App\Enums\LoanType;
use App\Models\Loan;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Loan
 */
class LoanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'contact_id' => $this->contact_id,
            'contact' => $this->whenLoaded('contact', fn () => ['id' => $this->contact->id, 'name' => $this->contact->name]),
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => ['id' => $this->account->id, 'name' => $this->account->name]),
            'amount' => $this->amount,
            'loan_date' => $this->loan_date->format('Y-m-d'),
            'expected_return_date' => $this->expected_return_date?->format('Y-m-d'),
            'note' => $this->note,
            'progress' => $this->progress(),
            'repayments' => LoanRepaymentResource::collection($this->whenLoaded('repayments')),
            'transfers' => LoanTransferResource::collection($this->whenLoaded('transfers')),
            'attachments' => LoanAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Uses the pre-aggregated sums when a list query supplied them, so a page of
     * loans does not run several queries per row.
     *
     * @return array<string, string>
     */
    private function progress(): array
    {
        $loan = $this->resource;

        if (! array_key_exists('repayments_sum_amount', $loan->getAttributes())) {
            return app(LoanCalculator::class)->progress($loan)->toArray();
        }

        $repaid = Money::of($loan->getAttribute('repayments_sum_amount') ?? 0);
        $transferred = Money::of($loan->getAttribute('transfers_sum_amount') ?? 0);

        return [
            'loan_id' => $loan->id,
            'total_repaid' => $repaid->toDecimalString(),
            'outstanding' => Money::of($loan->amount)->subtract($repaid)->toDecimalString(),
            'total_transferred' => $transferred->toDecimalString(),
            'held_balance' => $loan->type === LoanType::Given
                ? $repaid->subtract($transferred)->toDecimalString()
                : Money::zero()->toDecimalString(),
        ];
    }
}
