<?php

namespace App\Http\Resources;

use App\Models\SavingsGoal;
use App\Services\Finance\SavingsCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavingsGoal
 */
class SavingsGoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = app(SavingsCalculator::class)->summarize($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_amount' => $this->target_amount,
            'target_date' => $this->target_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status->value,
            'saved_amount' => $summary->savedAmount->toDecimalString(),
            'remaining_amount' => $summary->remainingAmount->toDecimalString(),
            'usage_percentage' => $summary->usagePercentage,
            'transactions' => SavingsTransactionResource::collection($this->whenLoaded('transactions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
