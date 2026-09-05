<?php

namespace App\Http\Requests\Finance;

use App\Enums\LoanType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LoanType::class)],
            'contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('user_id', $this->user()->id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'loan_date' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'note' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ];
    }
}
