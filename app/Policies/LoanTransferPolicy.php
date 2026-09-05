<?php

namespace App\Policies;

use App\Models\LoanTransfer;
use App\Models\User;

class LoanTransferPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LoanTransfer $transfer): bool
    {
        return $user->id === $transfer->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LoanTransfer $transfer): bool
    {
        return $user->id === $transfer->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LoanTransfer $transfer): bool
    {
        return $user->id === $transfer->user_id;
    }
}
