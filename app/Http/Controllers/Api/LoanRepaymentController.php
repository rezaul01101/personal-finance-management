<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\TranslatesLoanBalanceErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLoanRepaymentRequest;
use App\Http\Requests\Finance\UpdateLoanRepaymentRequest;
use App\Http\Resources\LoanRepaymentResource;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Services\Finance\LoanRepaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class LoanRepaymentController extends Controller
{
    use TranslatesLoanBalanceErrors;

    public function __construct(private readonly LoanRepaymentService $repayments) {}

    #[Authorize('view', 'loan')]
    public function store(StoreLoanRepaymentRequest $request, Loan $loan): JsonResponse
    {
        $repayment = $this->withBalanceErrors(
            fn () => $this->repayments->create($request->user(), $loan, $request->validated()),
        );

        return (new LoanRepaymentResource($repayment->load('account')))->response()->setStatusCode(201);
    }

    #[Authorize('view', 'repayment')]
    public function show(LoanRepayment $repayment): LoanRepaymentResource
    {
        return new LoanRepaymentResource($repayment->load('account'));
    }

    #[Authorize('update', 'repayment')]
    public function update(UpdateLoanRepaymentRequest $request, LoanRepayment $repayment): LoanRepaymentResource
    {
        $this->withBalanceErrors(fn () => $this->repayments->update($repayment, $request->validated()));

        return new LoanRepaymentResource($repayment->load('account'));
    }

    #[Authorize('delete', 'repayment')]
    public function destroy(LoanRepayment $repayment): Response
    {
        $this->withBalanceErrors(fn () => $this->repayments->delete($repayment));

        return response()->noContent();
    }
}
