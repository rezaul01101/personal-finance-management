<?php

namespace App\Http\Requests\Finance;

use App\Enums\LoanType;
use App\Models\Loan;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanTransferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * A transfer only ever applies to a loan given - reject before
     * validating anything else so a loan taken 404s here exactly like it
     * does on the `create`/`store` controller actions, rather than surfacing
     * a confusing "exceeds held balance" validation error instead.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LoanCalculator $loanCalculator): array
    {
        /** @var Loan $loan */
        $loan = $this->route('loan');

        abort_unless($loan->type === LoanType::Given, 404);

        return [
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($loanCalculator, $loan) {
                    if (Money::of((string) $value)->compareTo($loanCalculator->heldBalance($loan)) > 0) {
                        $fail('This transfer exceeds the amount currently held for this loan.');
                    }
                },
            ],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transferred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
