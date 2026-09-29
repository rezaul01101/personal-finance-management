<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'spent_on' => $this->spent_on->toDateString(),
            'note' => $this->note,
            'expense_category_id' => $this->expense_category_id,
            'budget_category_id' => $this->budget_category_id,
            'account_id' => $this->account_id,
            'expense_category' => $this->whenLoaded('expenseCategory', fn () => [
                'id' => $this->expenseCategory->id,
                'name' => $this->expenseCategory->name,
            ]),
            'budget_category' => $this->whenLoaded('budgetCategory', fn () => [
                'id' => $this->budgetCategory->id,
                'name' => $this->budgetCategory->name,
                'icon' => $this->budgetCategory->icon,
            ]),
            'account' => $this->whenLoaded('account', fn () => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ]),
            'attachments' => ExpenseAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
