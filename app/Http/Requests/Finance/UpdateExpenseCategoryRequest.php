<?php

namespace App\Http\Requests\Finance;

use App\Enums\CategoryStatus;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ExpenseCategory $expenseCategory */
        $expenseCategory = $this->route('expense_category');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($expenseCategory->id),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(CategoryStatus::class)],
        ];
    }
}
