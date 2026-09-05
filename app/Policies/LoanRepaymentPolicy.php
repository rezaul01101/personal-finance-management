<?php

namespace App\Policies;

use App\Models\LoanRepayment;
use App\Models\User;

class LoanRepaymentPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LoanRepayment $repayment): bool
    {
        return $user->id === $repayment->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LoanRepayment $repayment): bool
    {
        return $user->id === $repayment->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LoanRepayment $repayment): bool
    {
        return $user->id === $repayment->user_id;
    }
}
