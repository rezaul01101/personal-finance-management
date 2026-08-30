<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_category_id' => [
                'required',
                Rule::exists('expense_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'budget_category_id' => [
                'required',
                Rule::exists('budget_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'spent_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'receipts' => ['nullable', 'array', 'max:5'],
            'receipts.*' => ['image', 'max:5120'],
        ];
    }
}
