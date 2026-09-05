<?php

namespace App\Http\Requests\Finance;

use App\Models\Loan;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Direction (`type`) is intentionally not validated here - it is
     * immutable after creation, since flipping given/taken would corrupt
     * the account effects already recorded against it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LoanCalculator $loanCalculator): array
    {
        /** @var Loan $loan */
        $loan = $this->route('loan');

        return [
            'contact_id' => [
                'required',
                Rule::exists('contacts', 'id')->where('user_id', $this->user()->id),
            ],
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($loanCalculator, $loan) {
                    if (Money::of((string) $value)->compareTo($loanCalculator->totalRepaid($loan)) < 0) {
                        $fail('The loan amount cannot be less than what has already been repaid.');
                    }
                },
            ],
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
