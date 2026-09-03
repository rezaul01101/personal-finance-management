<?php

namespace App\Http\Requests\Finance;

use App\Enums\SavingsTransactionType;
use App\Models\SavingsGoal;
use App\Services\Finance\Money;
use App\Services\Finance\SavingsCalculator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavingsTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(SavingsCalculator $savingsCalculator): array
    {
        /** @var SavingsGoal $savingsGoal */
        $savingsGoal = $this->route('savings_goal');

        return [
            'type' => ['required', Rule::enum(SavingsTransactionType::class)],
            'amount' => [
                'bail',
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, Closure $fail) use ($savingsCalculator, $savingsGoal) {
                    if ($this->input('type') !== SavingsTransactionType::Withdrawal->value) {
                        return;
                    }

                    if (Money::of((string) $value)->compareTo($savingsCalculator->balance($savingsGoal)) > 0) {
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
