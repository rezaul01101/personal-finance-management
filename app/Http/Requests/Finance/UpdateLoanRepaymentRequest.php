<?php

namespace App\Http\Requests\Finance;

use App\Enums\LoanType;
use App\Models\LoanRepayment;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRepaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LoanCalculator $loanCalculator): array
    {
        /** @var LoanRepayment $repayment */
        $repayment = $this->route('repayment');
        $loan = $repayment->loan;

        // The outstanding balance if this repayment's own current effect is
        // undone first, so editing it is checked against the loan as it
        // would be without the old entry, not with it double-counted.
        $outstandingExcludingSelf = $loanCalculator->outstanding($loan)->add(Money::of($repayment->amount));

        return [
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($outstandingExcludingSelf) {
                    if (Money::of((string) $value)->compareTo($outstandingExcludingSelf) > 0) {
                        $fail('This repayment exceeds the loan\'s outstanding balance.');
                    }
                },
            ],
            'account_id' => [
                Rule::requiredIf($loan->type === LoanType::Taken),
                'nullable',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'repaid_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
