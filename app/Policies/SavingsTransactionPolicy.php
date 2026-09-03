<?php

namespace App\Policies;

use App\Models\SavingsTransaction;
use App\Models\User;

class SavingsTransactionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavingsTransaction $savingsTransaction): bool
    {
        return $user->id === $savingsTransaction->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavingsTransaction $savingsTransaction): bool
    {
        return $user->id === $savingsTransaction->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavingsTransaction $savingsTransaction): bool
    {
        return $user->id === $savingsTransaction->user_id;
    }
}
