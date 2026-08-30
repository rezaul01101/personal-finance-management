<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseAttachmentController extends Controller
{
    /**
     * Remove a single receipt/image attached to an expense.
     */
    #[Authorize('delete', 'attachment')]
    public function destroy(Expense $expense, ExpenseAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->expense_id === $expense->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment removed.')]);

        return to_route('expenses.edit', $expense);
    }
}
