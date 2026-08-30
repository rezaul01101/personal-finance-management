<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Income;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete an Income - keeps the
 * account-balance side effect (spec Rule 7/9/18) atomic and consistent with
 * every write, including the reversal-then-reapply required when editing.
 */
final class IncomeService
{
    public function __construct(private readonly AccountBalanceService $accountBalance) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): Income
    {
        return DB::transaction(function () use ($user, $attributes) {
            $income = $user->incomes()->create($attributes);

            $this->accountBalance->credit($income->account, Money::of($income->amount));

            return $income;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Income $income, array $attributes): Income
    {
        return DB::transaction(function () use ($income, $attributes) {
            $oldAccount = $income->account;
            $oldAmount = Money::of($income->amount);

            $this->accountBalance->debit($oldAccount, $oldAmount);

            $income->update($attributes);

            $newAccount = $income->account_id === $oldAccount->id
                ? $oldAccount
                : Account::query()->findOrFail($income->account_id);

            $this->accountBalance->credit($newAccount, Money::of($income->amount));

            return $income;
        });
    }

    public function delete(Income $income): void
    {
        DB::transaction(function () use ($income) {
            $this->accountBalance->debit($income->account, Money::of($income->amount));

            $income->delete();
        });
    }
}
