<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;

class SavingsGoalPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->id === $savingsGoal->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->id === $savingsGoal->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavingsGoal $savingsGoal): bool
    {
        return $user->id === $savingsGoal->user_id;
    }
}
