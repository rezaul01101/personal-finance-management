<?php

namespace App\Policies;

use App\Models\AccountTransfer;
use App\Models\User;

class AccountTransferPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AccountTransfer $accountTransfer): bool
    {
        return $user->id === $accountTransfer->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AccountTransfer $accountTransfer): bool
    {
        return $user->id === $accountTransfer->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AccountTransfer $accountTransfer): bool
    {
        return $user->id === $accountTransfer->user_id;
    }
}
