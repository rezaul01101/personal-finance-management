<?php

namespace App\Http\Requests\Finance;

use App\Models\LoanTransfer;
use App\Services\Finance\LoanCalculator;
use App\Services\Finance\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanTransferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(LoanCalculator $loanCalculator): array
    {
        /** @var LoanTransfer $transfer */
        $transfer = $this->route('transfer');
        $loan = $transfer->loan;

        // The held balance if this transfer's own current effect is undone
        // first, so editing it is checked against the loan as it would be
        // without the old entry, not with it double-counted.
        $heldBalanceExcludingSelf = $loanCalculator->heldBalance($loan)->add(Money::of($transfer->amount));

        return [
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($heldBalanceExcludingSelf) {
                    if (Money::of((string) $value)->compareTo($heldBalanceExcludingSelf) > 0) {
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
