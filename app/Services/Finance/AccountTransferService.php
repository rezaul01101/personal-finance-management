<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AccountTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only code path allowed to create/edit/delete an AccountTransfer - moves
 * money between two of the user's own accounts without ever being treated as
 * income or an expense (spec Rule 6/9/20).
 */
final class AccountTransferService
{
    public function __construct(private readonly AccountBalanceService $accountBalance) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): AccountTransfer
    {
        return DB::transaction(function () use ($user, $attributes) {
            $transfer = $user->accountTransfers()->create($attributes);

            $amount = Money::of($transfer->amount);
            $this->accountBalance->debit($transfer->fromAccount, $amount);
            $this->accountBalance->credit($transfer->toAccount, $amount);

            return $transfer;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(AccountTransfer $transfer, array $attributes): AccountTransfer
    {
        return DB::transaction(function () use ($transfer, $attributes) {
            $oldFromAccount = $transfer->fromAccount;
            $oldToAccount = $transfer->toAccount;
            $oldAmount = Money::of($transfer->amount);

            $this->accountBalance->credit($oldFromAccount, $oldAmount);
            $this->accountBalance->debit($oldToAccount, $oldAmount);

            $transfer->update($attributes);

            $newFromAccount = $transfer->from_account_id === $oldFromAccount->id
                ? $oldFromAccount
                : Account::query()->findOrFail($transfer->from_account_id);
            $newToAccount = $transfer->to_account_id === $oldToAccount->id
                ? $oldToAccount
                : Account::query()->findOrFail($transfer->to_account_id);

            $newAmount = Money::of($transfer->amount);
            $this->accountBalance->debit($newFromAccount, $newAmount);
            $this->accountBalance->credit($newToAccount, $newAmount);

            return $transfer;
        });
    }

    public function delete(AccountTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $amount = Money::of($transfer->amount);

            $this->accountBalance->credit($transfer->fromAccount, $amount);
            $this->accountBalance->debit($transfer->toAccount, $amount);

            $transfer->delete();
        });
    }
}
