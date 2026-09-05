<?php

namespace App\Policies;

use App\Models\LoanAttachment;
use App\Models\User;

class LoanAttachmentPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LoanAttachment $attachment): bool
    {
        return $user->id === $attachment->loan->user_id;
    }
}
