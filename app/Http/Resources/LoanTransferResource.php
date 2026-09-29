<?php

namespace App\Http\Resources;

use App\Models\LoanTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoanTransfer
 */
class LoanTransferResource extends JsonResource
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
            'transferred_on' => $this->transferred_on->format('Y-m-d'),
            'note' => $this->note,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
