<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => [
                'nullable',
                Rule::exists('expense_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'budget_category_id' => [
                'nullable',
                Rule::exists('budget_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The validated filters, keyed by filter name, with blanks omitted.
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        return array_filter($this->validated(), fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
