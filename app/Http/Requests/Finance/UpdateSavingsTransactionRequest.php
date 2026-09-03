<?php

namespace App\Http\Requests\Finance;

use App\Enums\SavingsTransactionType;
use App\Models\SavingsTransaction;
use App\Services\Finance\Money;
use App\Services\Finance\SavingsCalculator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavingsTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(SavingsCalculator $savingsCalculator): array
    {
        /** @var SavingsTransaction $transaction */
        $transaction = $this->route('transaction');

        // The balance if this transaction's own current effect is undone
        // first, so editing it (even increasing a withdrawal) is checked
        // against the goal as it would be without the old entry, not with
        // it double-counted.
        $selfEffect = $transaction->type === SavingsTransactionType::Contribution
            ? Money::of($transaction->amount)
            : Money::of($transaction->amount)->multiply('-1');
        $balanceExcludingSelf = $savingsCalculator->balance($transaction->savingsGoal)->subtract($selfEffect);

        return [
            'type' => ['required', Rule::enum(SavingsTransactionType::class)],
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($balanceExcludingSelf) {
                    if ($this->input('type') !== SavingsTransactionType::Withdrawal->value) {
                        return;
                    }

                    if (Money::of((string) $value)->compareTo($balanceExcludingSelf) > 0) {
                        $fail('This withdrawal exceeds the currently saved amount.');
                    }
                },
            ],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transacted_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
