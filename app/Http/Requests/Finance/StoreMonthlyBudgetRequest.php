<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyBudgetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'budgets' => ['required', 'array'],
            'budgets.*.budget_category_id' => [
                'required',
                Rule::exists('budget_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'budgets.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
