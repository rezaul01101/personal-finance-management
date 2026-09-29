<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanAttachmentResource;
use App\Models\Loan;
use App\Models\LoanAttachment;
use App\Services\Finance\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Storage;

class LoanAttachmentController extends Controller
{
    public function __construct(private readonly LoanService $loans) {}

    /**
     * Add photos to an existing loan, with the same limits as the loan form.
     */
    #[Authorize('update', 'loan')]
    public function store(Request $request, Loan $loan): JsonResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $existingIds = $loan->attachments()->pluck('id');

        $this->loans->update($loan, [], $request->file('photos'));

        $created = $loan->attachments()->whereNotIn('id', $existingIds)->orderBy('id')->get();

        return LoanAttachmentResource::collection($created)->response()->setStatusCode(201);
    }

    #[Authorize('delete', 'attachment')]
    public function destroy(Loan $loan, LoanAttachment $attachment): Response
    {
        abort_unless($attachment->loan_id === $loan->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
