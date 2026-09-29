<?php

namespace App\Http\Resources;

use App\Models\Account;
use App\Services\Finance\AccountCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = app(AccountCalculator::class)->summarize($this->resource)->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'status' => $this->status,
            'balance' => $summary['current_balance'],
            'opening_balance' => $summary['opening_balance'],
            'total_credits' => $summary['total_credits'],
            'total_debits' => $summary['total_debits'],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
