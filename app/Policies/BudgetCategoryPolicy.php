<?php

namespace App\Policies;

use App\Models\BudgetCategory;
use App\Models\User;

class BudgetCategoryPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BudgetCategory $budgetCategory): bool
    {
        return $user->id === $budgetCategory->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BudgetCategory $budgetCategory): bool
    {
        return $user->id === $budgetCategory->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BudgetCategory $budgetCategory): bool
    {
        return $user->id === $budgetCategory->user_id;
    }
}
