<?php

namespace App\Http\Resources;

use App\Models\LoanRepayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoanRepayment
 */
class LoanRepaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'amount' => $this->amount,
            'repaid_on' => $this->repaid_on->format('Y-m-d'),
            'note' => $this->note,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => $this->account
                ? ['id' => $this->account->id, 'name' => $this->account->name]
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
