<?php

namespace App\Policies;

use App\Models\ExpenseAttachment;
use App\Models\User;

class ExpenseAttachmentPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExpenseAttachment $attachment): bool
    {
        return $user->id === $attachment->expense->user_id;
    }
}
