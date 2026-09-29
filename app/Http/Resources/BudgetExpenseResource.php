<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An expense as listed on a budget category's month view.
 *
 * @mixin Expense
 */
class BudgetExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'spent_on' => $this->spent_on->toDateString(),
            'note' => $this->note,
            'expense_category' => [
                'id' => $this->expenseCategory->id,
                'name' => $this->expenseCategory->name,
                'icon' => $this->expenseCategory->icon,
            ],
            'account' => [
                'id' => $this->account->id,
                'name' => $this->account->name,
            ],
        ];
    }
}
