<?php

namespace App\Http\Resources;

use App\Models\AccountTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AccountTransfer
 */
class AccountTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'transferred_on' => $this->transferred_on->toDateString(),
            'note' => $this->note,
            'from_account_id' => $this->from_account_id,
            'to_account_id' => $this->to_account_id,
            'from_account' => $this->whenLoaded('fromAccount', fn () => [
                'id' => $this->fromAccount->id,
                'name' => $this->fromAccount->name,
            ]),
            'to_account' => $this->whenLoaded('toAccount', fn () => [
                'id' => $this->toAccount->id,
                'name' => $this->toAccount->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
