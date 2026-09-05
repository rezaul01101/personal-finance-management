<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }
}
