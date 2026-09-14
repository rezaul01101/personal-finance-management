<?php

namespace App\Services\Finance;

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
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): AccountTransfer
    {
        return DB::transaction(function () use ($user, $attributes) {
            return $user->accountTransfers()->create($attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(AccountTransfer $transfer, array $attributes): AccountTransfer
    {
        return DB::transaction(function () use ($transfer, $attributes) {
            $transfer->update($attributes);

            return $transfer;
        });
    }

    public function delete(AccountTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer->delete();
        });
    }
}
