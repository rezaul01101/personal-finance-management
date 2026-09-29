<?php

namespace App\Http\Controllers\Api;

use App\Enums\LoanType;
use App\Http\Controllers\Api\Concerns\TranslatesLoanBalanceErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanTransferRequest;
use App\Http\Requests\Finance\UpdateLoanTransferRequest;
use App\Http\Resources\LoanTransferResource;
use App\Models\Loan;
use App\Models\LoanTransfer;
use App\Services\Finance\LoanTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

/**
 * Transfers only exist for loans given; a loan taken 404s.
 */
class LoanTransferController extends Controller
{
    use TranslatesLoanBalanceErrors;

    public function __construct(private readonly LoanTransferService $transfers) {}

    #[Authorize('view', 'loan')]
    public function store(StoreLoanTransferRequest $request, Loan $loan): JsonResponse
    {
        abort_unless($loan->type === LoanType::Given, 404);

        $transfer = $this->withBalanceErrors(
            fn () => $this->transfers->create($request->user(), $loan, $request->validated()),
        );

        return (new LoanTransferResource($transfer->load('account')))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'transfer')]
    public function show(Loan $loan, LoanTransfer $transfer): LoanTransferResource
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        return new LoanTransferResource($transfer->load('account'));
    }

    #[Authorize('update', 'transfer')]
    public function update(UpdateLoanTransferRequest $request, Loan $loan, LoanTransfer $transfer): LoanTransferResource
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        $this->withBalanceErrors(fn () => $this->transfers->update($transfer, $request->validated()));

        return new LoanTransferResource($transfer->load('account'));
    }

    #[Authorize('delete', 'transfer')]
    public function destroy(Loan $loan, LoanTransfer $transfer): Response
    {
        abort_unless($transfer->loan_id === $loan->id, 404);

        $this->transfers->delete($transfer);

        return response()->noContent();
    }
}
