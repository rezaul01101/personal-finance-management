<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LoanAttachmentController extends Controller
{
    /**
     * Remove a single photo attached to a loan.
     */
    #[Authorize('delete', 'attachment')]
    public function destroy(Loan $loan, LoanAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->loan_id === $loan->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment removed.')]);

        return to_route('loans.edit', $loan);
    }
}
