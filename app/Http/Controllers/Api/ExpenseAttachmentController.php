<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ExpenseAttachmentController extends Controller
{
    public function destroy(Expense $expense, ExpenseAttachment $attachment): Response
    {
        Gate::authorize('delete', $attachment);

        abort_unless($attachment->expense_id === $expense->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
